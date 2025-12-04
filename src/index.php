<?php

// Database configuration
$dbHost = getenv('DB_HOST') ?: 'postgres';
$dbName = getenv('DB_NAME') ?: 'todoapp';
$dbUser = getenv('DB_USER') ?: 'todouser';
$dbPass = getenv('DB_PASS') ?: 'todopass';

// API endpoint handling
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Simple routing
if (strpos($requestUri, '/api/') === 0) {
    header('Content-Type: application/json');
    
    try {
        $pdo = new PDO("pgsql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($requestUri === '/api/todos' || strpos($requestUri, '/api/todos?') === 0) {
            if ($requestMethod === 'GET') {
                // Get all todos
                $stmt = $pdo->query('SELECT * FROM todos ORDER BY created_at DESC');
                $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $todos]);
            } elseif ($requestMethod === 'POST') {
                // Create new todo
                $data = json_decode(file_get_contents('php://input'), true);
                $stmt = $pdo->prepare('INSERT INTO todos (title, description) VALUES (:title, :description)');
                $stmt->execute([
                    'title' => $data['title'] ?? '',
                    'description' => $data['description'] ?? ''
                ]);
                echo json_encode(['success' => true, 'message' => 'Todo created']);
            }
        } elseif (preg_match('/^\/api\/todos\/(\d+)$/', $requestUri, $matches)) {
            $todoId = $matches[1];
            
            if ($requestMethod === 'PUT') {
                // Update todo
                $data = json_decode(file_get_contents('php://input'), true);
                $stmt = $pdo->prepare('UPDATE todos SET completed = :completed WHERE id = :id');
                $stmt->execute([
                    'completed' => $data['completed'] ?? false,
                    'id' => $todoId
                ]);
                echo json_encode(['success' => true, 'message' => 'Todo updated']);
            } elseif ($requestMethod === 'DELETE') {
                // Delete todo
                $stmt = $pdo->prepare('DELETE FROM todos WHERE id = :id');
                $stmt->execute(['id' => $todoId]);
                echo json_encode(['success' => true, 'message' => 'Todo deleted']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo Tracker - CI/CD Demo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        .todo-form {
            margin-bottom: 20px;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .todo-list {
            list-style: none;
            padding: 0;
        }
        .todo-item {
            background-color: #f9f9f9;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #4CAF50;
        }
        .todo-item.completed {
            opacity: 0.6;
            border-left-color: #ccc;
        }
        .todo-item.completed .todo-title {
            text-decoration: line-through;
        }
        .todo-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .todo-description {
            color: #666;
            margin-bottom: 10px;
        }
        .todo-actions button {
            margin-right: 5px;
            font-size: 12px;
            padding: 5px 10px;
        }
        .delete-btn {
            background-color: #f44336;
        }
        .delete-btn:hover {
            background-color: #da190b;
        }
        .status {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .status.success {
            background-color: #d4edda;
            color: #155724;
        }
        .status.error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📝 Todo Tracker - CI/CD Demo</h1>
        <p>Simple todo application demonstrating CI/CD pipeline with Docker, PostgreSQL, and automated testing.</p>
        
        <div class="todo-form">
            <h2>Add New Todo</h2>
            <input type="text" id="todoTitle" placeholder="Todo title" required>
            <textarea id="todoDescription" placeholder="Description (optional)" rows="3"></textarea>
            <button onclick="addTodo()">Add Todo</button>
        </div>
        
        <div id="status"></div>
        
        <div>
            <h2>Todo List</h2>
            <ul class="todo-list" id="todoList">
                <li>Loading todos...</li>
            </ul>
        </div>
    </div>

    <script>
        // Load todos on page load
        document.addEventListener('DOMContentLoaded', loadTodos);

        function showStatus(message, isError = false) {
            const statusDiv = document.getElementById('status');
            statusDiv.textContent = message;
            statusDiv.className = 'status ' + (isError ? 'error' : 'success');
            setTimeout(() => {
                statusDiv.textContent = '';
                statusDiv.className = '';
            }, 3000);
        }

        async function loadTodos() {
            try {
                const response = await fetch('/api/todos');
                const result = await response.json();
                
                if (result.success) {
                    displayTodos(result.data);
                } else {
                    showStatus('Failed to load todos', true);
                }
            } catch (error) {
                showStatus('Error: ' + error.message, true);
                document.getElementById('todoList').innerHTML = '<li>Failed to load todos. Check database connection.</li>';
            }
        }

        function displayTodos(todos) {
            const todoList = document.getElementById('todoList');
            
            if (todos.length === 0) {
                todoList.innerHTML = '<li>No todos yet. Add one above!</li>';
                return;
            }
            
            todoList.innerHTML = todos.map(todo => `
                <li class="todo-item ${todo.completed ? 'completed' : ''}" data-id="${todo.id}">
                    <div class="todo-title">${escapeHtml(todo.title)}</div>
                    <div class="todo-description">${escapeHtml(todo.description || '')}</div>
                    <div class="todo-actions">
                        <button onclick="toggleTodo(${todo.id}, ${todo.completed})">
                            ${todo.completed ? 'Mark Incomplete' : 'Mark Complete'}
                        </button>
                        <button class="delete-btn" onclick="deleteTodo(${todo.id})">Delete</button>
                    </div>
                </li>
            `).join('');
        }

        async function addTodo() {
            const title = document.getElementById('todoTitle').value.trim();
            const description = document.getElementById('todoDescription').value.trim();
            
            if (!title) {
                showStatus('Please enter a title', true);
                return;
            }
            
            try {
                const response = await fetch('/api/todos', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ title, description })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showStatus('Todo added successfully!');
                    document.getElementById('todoTitle').value = '';
                    document.getElementById('todoDescription').value = '';
                    loadTodos();
                } else {
                    showStatus('Failed to add todo', true);
                }
            } catch (error) {
                showStatus('Error: ' + error.message, true);
            }
        }

        async function toggleTodo(id, currentStatus) {
            try {
                const response = await fetch(`/api/todos/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ completed: !currentStatus })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showStatus('Todo updated!');
                    loadTodos();
                } else {
                    showStatus('Failed to update todo', true);
                }
            } catch (error) {
                showStatus('Error: ' + error.message, true);
            }
        }

        async function deleteTodo(id) {
            if (!confirm('Are you sure you want to delete this todo?')) {
                return;
            }
            
            try {
                const response = await fetch(`/api/todos/${id}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showStatus('Todo deleted!');
                    loadTodos();
                } else {
                    showStatus('Failed to delete todo', true);
                }
            } catch (error) {
                showStatus('Error: ' + error.message, true);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
