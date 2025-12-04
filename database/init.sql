-- Initialize database for simple todo tracker
CREATE TABLE IF NOT EXISTS todos (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO todos (title, description, completed) VALUES
    ('Setup CI/CD Pipeline', 'Configure the complete CI/CD workflow', false),
    ('Add Unit Tests', 'Create comprehensive unit tests', false),
    ('Deploy to Production', 'Final deployment to production', false);
