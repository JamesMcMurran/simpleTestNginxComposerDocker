import { test, expect } from '@playwright/test';

test.describe('Todo App', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('should load the homepage', async ({ page }) => {
    await expect(page).toHaveTitle(/Todo Tracker/);
    await expect(page.locator('h1')).toContainText('Todo Tracker');
  });

  test('should display the todo form', async ({ page }) => {
    await expect(page.locator('#todoTitle')).toBeVisible();
    await expect(page.locator('#todoDescription')).toBeVisible();
    await expect(page.locator('button', { hasText: 'Add Todo' })).toBeVisible();
  });

  test('should load existing todos from API', async ({ page }) => {
    // Wait for todos to load
    await page.waitForSelector('.todo-list');
    
    const todoList = page.locator('.todo-list');
    await expect(todoList).toBeVisible();
    
    // Check if todos loaded (either showing todos or empty state)
    const hasContent = await todoList.locator('li').count() > 0;
    expect(hasContent).toBeTruthy();
  });

  test('should add a new todo', async ({ page }) => {
    const todoTitle = 'Test Todo - ' + Date.now();
    const todoDescription = 'This is a test todo for e2e testing';

    // Fill in the form
    await page.fill('#todoTitle', todoTitle);
    await page.fill('#todoDescription', todoDescription);
    
    // Click add button
    await page.click('button:has-text("Add Todo")');
    
    // Wait for success status
    await page.waitForSelector('.status.success', { timeout: 5000 });
    
    // Verify the todo appears in the list
    await expect(page.locator('.todo-item')).toContainText(todoTitle);
  });

  test('should mark todo as complete', async ({ page }) => {
    // Wait for todos to load
    await page.waitForSelector('.todo-list');
    
    // Find the first incomplete todo
    const incompleteTodo = page.locator('.todo-item:not(.completed)').first();
    
    // Check if there are any incomplete todos
    const count = await incompleteTodo.count();
    if (count > 0) {
      await incompleteTodo.locator('button:has-text("Mark Complete")').click();
      
      // Wait for success status
      await page.waitForSelector('.status.success', { timeout: 5000 });
      
      // Verify the todo is marked as completed
      await expect(incompleteTodo).toHaveClass(/completed/);
    }
  });

  test('should delete a todo', async ({ page }) => {
    // First, add a todo to delete
    const todoTitle = 'Todo to Delete - ' + Date.now();
    await page.fill('#todoTitle', todoTitle);
    await page.click('button:has-text("Add Todo")');
    await page.waitForSelector('.status.success', { timeout: 5000 });
    
    // Find and delete the todo
    const todoItem = page.locator('.todo-item', { hasText: todoTitle });
    
    // Handle the confirmation dialog
    page.on('dialog', dialog => dialog.accept());
    
    await todoItem.locator('button.delete-btn').click();
    
    // Wait for success status
    await page.waitForSelector('.status.success', { timeout: 5000 });
    
    // Verify the todo is removed
    await expect(todoItem).not.toBeVisible();
  });

  test('should validate empty todo title', async ({ page }) => {
    // Try to add todo without title
    await page.click('button:has-text("Add Todo")');
    
    // Should show error status
    await expect(page.locator('.status.error')).toBeVisible();
    await expect(page.locator('.status.error')).toContainText('Please enter a title');
  });

  test('API endpoints should return JSON', async ({ page }) => {
    const response = await page.request.get('/api/todos');
    expect(response.ok()).toBeTruthy();
    
    const data = await response.json();
    expect(data).toHaveProperty('success');
    expect(data).toHaveProperty('data');
  });
});
