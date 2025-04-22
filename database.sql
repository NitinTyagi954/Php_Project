-- Create database if not exists
CREATE DATABASE IF NOT EXISTS volunteer_portal2;
USE volunteer_portal2;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'user') DEFAULT 'user',
    remember_token VARCHAR(100),
    token_expiry DATETIME,
    total_hours INT DEFAULT 0,
    impact_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Create events table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    max_volunteers INT NOT NULL,
    current_volunteers INT DEFAULT 0,
    requires_approval BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create event_registrations table
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'registered', 'attended', 'cancelled', 'rejected') DEFAULT 'registered',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (event_id) REFERENCES events(id),
    UNIQUE KEY unique_registration (user_id, event_id)
);

-- Certificates table
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    event_id INT,
    certificate_name VARCHAR(255),
    issue_date DATE,
    hours_earned INT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (event_id) REFERENCES events(id)
);

-- Insert sample user
INSERT INTO users (full_name, email, password, role) VALUES 
('Test Volunteer', 'volunteer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'), -- password is 'password'
('Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'); -- password is 'password'

-- Insert sample events
INSERT INTO events (title, description, category, location, event_date, start_time, end_time, max_volunteers, requires_approval) VALUES
('Community Cleanup Day', 'Join us for a day of cleaning and beautifying our local parks and streets. Help make our community a cleaner and more beautiful place to live.', 'Environment', 'Riverside Park', '2025-04-15', '09:00:00', '12:00:00', 20, false),
('Food Drive for Families', 'Help us collect and distribute food to families in need in our community. Volunteers will assist with food collection, sorting, and distribution.', 'Food Security', 'Community Center', '2025-04-20', '10:00:00', '14:00:00', 15, false),
('Literacy Workshop', 'Assist in teaching children to read and write at our community literacy program. No teaching experience required - training will be provided.', 'Education', 'Local Library', '2025-04-25', '13:00:00', '16:00:00', 10, true),
('Senior Care Program', 'Spend time with elderly community members, assist with activities, and provide companionship. Help make a difference in the lives of our seniors.', 'Community Care', 'Senior Center', '2025-04-30', '10:00:00', '15:00:00', 12, true),
('Animal Shelter Support', 'Help care for animals at our local shelter. Tasks include feeding, cleaning, and socializing with the animals.', 'Animal Welfare', 'City Animal Shelter', '2025-05-05', '09:00:00', '13:00:00', 8, false); 