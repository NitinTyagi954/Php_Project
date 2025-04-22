-- Insert test user
INSERT INTO users (full_name, email, password, phone, total_hours, impact_score) VALUES 
('Test Volunteer', 'volunteer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567890', 42, 78);

-- Insert sample events
INSERT INTO events (title, description, category, location, event_date, start_time, end_time, max_volunteers) VALUES
('Community Cleanup', 'Join us for a day of cleaning and beautifying our local parks and streets.', 'Environment', 'Riverside Park', '2025-04-15', '09:00:00', '12:00:00', 20),
('Food Bank Assistance', 'Help sort and package food donations for distribution to those in need.', 'Social Services', 'Community Food Bank', '2025-04-20', '10:00:00', '14:00:00', 15),
('Youth Mentoring', 'Share your knowledge and experience with young people in our community.', 'Education', 'Youth Center', '2025-04-25', '15:00:00', '17:00:00', 10),
('Animal Shelter Support', 'Help care for animals and assist with adoption events.', 'Animal Welfare', 'Local Animal Shelter', '2025-05-01', '11:00:00', '16:00:00', 12),
('Senior Center Activities', 'Organize and lead activities for senior citizens.', 'Community', 'Senior Center', '2025-05-05', '13:00:00', '16:00:00', 8); 