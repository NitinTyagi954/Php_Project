document.addEventListener('DOMContentLoaded', () => {
    // Check if user is logged in
    checkSession();
    
    // Load all dashboard data
    loadUserStats();
    loadUpcomingEvents();
    loadUserEvents();
    
    // Add event listeners
    document.getElementById('profileBtn').addEventListener('click', () => {
        window.location.href = 'profile.html';
    });
    
    document.getElementById('logoutBtn').addEventListener('click', () => {
        logout();
    });
    
    // Initialize animations for scroll reveal
    initScrollReveal();
});

// Check user session
async function checkSession() {
    try {
        const response = await fetch('includes/check_session.php');
        const data = await response.json();
        
        if (!data.logged_in) {
            window.location.href = 'index.html';
        }
    } catch (error) {
        console.error('Session check failed:', error);
        showMessage('Session check failed. Redirecting to login page.', 'error');
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 2000);
    }
}

// Load user stats (impact score, hours, events count, etc.)
async function loadUserStats() {
    try {
        // Get user stats
        const statsResponse = await fetch('includes/get_user_stats.php');
        const statsData = await statsResponse.json();
        
        if (statsData.success) {
            // Update stats in the sidebar
            document.getElementById('impactScore').textContent = statsData.stats.impact_score;
            document.getElementById('totalHours').textContent = statsData.stats.total_hours;
        } else {
            throw new Error(statsData.message || 'Failed to load user stats');
        }
        
        // Get user events stats
        const eventsResponse = await fetch('includes/get_user_events.php');
        const eventsData = await eventsResponse.json();
        
        if (eventsData.success) {
            document.getElementById('totalEvents').textContent = eventsData.stats.total_events;
            document.getElementById('totalDays').textContent = eventsData.stats.total_days;
        }
    } catch (error) {
        console.error('Error loading user stats:', error);
        showMessage('Failed to load your stats. Please refresh the page.', 'error');
    }
}

// Load upcoming events from the database
async function loadUpcomingEvents() {
    try {
        // First fetch user's registered events to check registration status
        const userEventsResponse = await fetch('includes/get_user_events.php');
        const userEventsData = await userEventsResponse.json();
        
        // Create a map of events the user is registered for
        const registeredEvents = new Map();
        const cancelledEvents = new Map();
        
        if (userEventsData.success) {
            userEventsData.events.forEach(event => {
                if (event.status === 'registered' || event.status === 'attended' || event.status === 'pending') {
                    registeredEvents.set(parseInt(event.id), event.status);
                } else if (event.status === 'cancelled') {
                    cancelledEvents.set(parseInt(event.id), true);
                }
            });
        }
        
        // Then fetch all upcoming events
        const response = await fetch('includes/get_events.php');
        const data = await response.json();
        
        if (data.success) {
            const eventsList = document.getElementById('eventsList');
            
            if (data.events.length === 0) {
                eventsList.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-calendar-alt text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-600">No upcoming events available</p>
                    </div>
                `;
                return;
            }
            
            // Display events
            eventsList.innerHTML = data.events.map(event => {
                const eventDate = new Date(event.event_date);
                const formattedDate = eventDate.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                
                // Check if user is registered for this event
                const isRegistered = registeredEvents.has(parseInt(event.id));
                const wasCancelled = cancelledEvents.has(parseInt(event.id));
                const registrationStatus = isRegistered ? registeredEvents.get(parseInt(event.id)) : null;
                
                let buttonHtml = '';
                
                if (isRegistered) {
                    if (registrationStatus === 'pending') {
                        buttonHtml = `<button disabled class="bg-yellow-500 text-white px-4 py-2 rounded-md text-sm">
                            Pending Approval
                        </button>`;
                    } else {
                        buttonHtml = `<button disabled class="bg-green-500 text-white px-4 py-2 rounded-md text-sm">
                            Already Registered
                        </button>`;
                    }
                } else if (event.current_volunteers >= event.max_volunteers) {
                    buttonHtml = `<button disabled class="bg-gray-300 text-gray-600 px-4 py-2 rounded-md text-sm">
                        Full
                    </button>`;
                } else {
                    buttonHtml = `<button class="register-btn bg-primary text-white px-4 py-2 rounded-md text-sm hover:bg-primary-dark transition-colors" 
                        data-event-id="${event.id}">
                        ${event.requires_approval ? 'Request to Join' : (wasCancelled ? 'Register Again' : 'Register')}
                    </button>`;
                }
                
                return `
                    <div class="bg-gradient-to-r from-white to-blue-50 rounded-lg p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-tertiary mb-2">${event.title}</h3>
                                <p class="text-gray-600 mb-3">${event.description.substring(0, 120)}${event.description.length > 120 ? '...' : ''}</p>
                                <div class="flex flex-wrap gap-3 mb-4">
                                    <span class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-calendar-day text-primary mr-2"></i> ${formattedDate}
                                    </span>
                                    <span class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-clock text-primary mr-2"></i> ${event.start_time} - ${event.end_time}
                                    </span>
                                    <span class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-map-marker-alt text-primary mr-2"></i> ${event.location}
                                    </span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600 mb-4">
                                    <i class="fas fa-users text-primary mr-2"></i>
                                    <span>${event.current_volunteers} / ${event.max_volunteers} volunteers</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-2">
                            ${buttonHtml}
                        </div>
                    </div>
                `;
            }).join('');
            
            // Add event listeners to register buttons
            const registerButtons = document.querySelectorAll('.register-btn');
            registerButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const eventId = this.getAttribute('data-event-id');
                    registerForEvent(eventId);
                });
            });
        } else {
            throw new Error(data.message || 'Failed to load events');
        }
    } catch (error) {
        console.error('Error loading events:', error);
        document.getElementById('eventsList').innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-exclamation-circle text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-600">Unable to load events at this time</p>
            </div>
        `;
    }
}

// Load user's registered events
async function loadUserEvents() {
    try {
        const response = await fetch('includes/get_user_events.php');
        const data = await response.json();
        
        if (data.success) {
            const userEvents = document.getElementById('userEvents');
            
            // Filter out cancelled events
            const activeEvents = data.events.filter(event => event.status !== 'cancelled');
            
            if (activeEvents.length === 0) {
                userEvents.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-calendar-check text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-600">You haven't registered for any events yet</p>
                    </div>
                `;
                return;
            }
            
            // Display user's events
            userEvents.innerHTML = activeEvents.map(event => {
                const eventDate = new Date(event.event_date);
                const formattedDate = eventDate.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                
                let statusBadge = '';
                if (event.status === 'pending') {
                    statusBadge = '<span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded">Pending Approval</span>';
                } else if (event.status === 'registered') {
                    statusBadge = '<span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Registered</span>';
                } else if (event.status === 'attended') {
                    statusBadge = '<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Attended</span>';
                }
                
                return `
                    <div class="bg-gradient-to-r from-white to-blue-50 rounded-lg p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex flex-col md:flex-row justify-between">
                            <div class="mb-4 md:mb-0">
                                <div class="flex items-center mb-2">
                                    <h3 class="text-lg font-semibold text-tertiary mr-3">${event.title}</h3>
                                    ${statusBadge}
                                </div>
                                <div class="flex flex-wrap gap-3 mb-3">
                                    <span class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-calendar-day text-primary mr-2"></i> ${formattedDate}
                                    </span>
                                    <span class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-clock text-primary mr-2"></i> ${event.start_time} - ${event.end_time}
                                    </span>
                                    <span class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-map-marker-alt text-primary mr-2"></i> ${event.location}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button class="view-members-btn bg-blue-500 text-white px-3 py-1.5 rounded text-sm hover:bg-blue-600 transition-colors"
                                    data-event-id="${event.id}" data-event-title="${event.title}">
                                    <i class="fas fa-users mr-1"></i> View Members
                                </button>
                                ${event.status !== 'attended' ? 
                                    `<button class="cancel-btn bg-red-500 text-white px-3 py-1.5 rounded text-sm hover:bg-red-600 transition-colors"
                                        data-event-id="${event.id}">
                                        <i class="fas fa-times mr-1"></i> Cancel
                                    </button>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            // Add event listeners
            document.querySelectorAll('.view-members-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const eventId = this.getAttribute('data-event-id');
                    const eventTitle = this.getAttribute('data-event-title');
                    loadEventMembers(eventId, eventTitle);
                });
            });
            
            document.querySelectorAll('.cancel-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const eventId = this.getAttribute('data-event-id');
                    cancelRegistration(eventId);
                });
            });
        } else {
            throw new Error(data.message || 'Failed to load your events');
        }
    } catch (error) {
        console.error('Error loading user events:', error);
        document.getElementById('userEvents').innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-exclamation-circle text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-600">Unable to load your events at this time</p>
            </div>
        `;
    }
}

// Load event members
async function loadEventMembers(eventId, eventTitle) {
    try {
        const response = await fetch(`includes/get_event_members.php?event_id=${eventId}`);
        const data = await response.json();
        
        if (data.success) {
            const eventMembers = document.getElementById('eventMembers');
            
            // Update section title
            eventMembers.innerHTML = `
                <h3 class="text-lg font-semibold text-tertiary mb-4">${eventTitle} - Registered Members</h3>
            `;
            
            if (data.members.length === 0) {
                eventMembers.innerHTML += `
                    <div class="text-center py-8">
                        <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-600">No members registered for this event yet</p>
                    </div>
                `;
            } else {
                // Create member list
                const memberList = document.createElement('div');
                memberList.className = 'grid grid-cols-1 md:grid-cols-2 gap-4';
                
                data.members.forEach(member => {
                    const memberCard = document.createElement('div');
                    memberCard.className = 'bg-white rounded-lg p-4 shadow-sm border border-gray-100';
                    memberCard.innerHTML = `
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-blue-500"></i>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-800">${member.full_name}</h4>
                                <p class="text-sm text-gray-500">Registered on ${new Date(member.registration_date).toLocaleDateString()}</p>
                            </div>
                        </div>
                    `;
                    memberList.appendChild(memberCard);
                });
                
                eventMembers.appendChild(memberList);
            }
            
            // Scroll to the event members section and position it in the middle of the screen
            const eventMembersSection = document.querySelector('.glass-effect');
            if (eventMembersSection) {
                eventMembersSection.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'center' 
                });
            }
            
        } else {
            throw new Error(data.message || 'Failed to load event members');
        }
    } catch (error) {
        console.error('Error loading event members:', error);
        document.getElementById('eventMembers').innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-exclamation-circle text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-600">Unable to load event members at this time</p>
            </div>
        `;
    }
}

// Register for an event
async function registerForEvent(eventId) {
    try {
        const response = await fetch('includes/register_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ event_id: eventId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message || 'Successfully registered for the event!', 'success');
            // Reload events data
            loadUpcomingEvents();
            loadUserEvents();
            loadUserStats();
        } else {
            showMessage(data.message || 'Failed to register for the event', 'error');
        }
    } catch (error) {
        console.error('Error registering for event:', error);
        showMessage('An error occurred while registering for the event', 'error');
    }
}

// Cancel registration for an event
async function cancelRegistration(eventId) {
    if (!confirm('Are you sure you want to cancel your registration for this event?')) {
        return;
    }
    
    try {
        const response = await fetch('includes/cancel_registration.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ event_id: eventId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message || 'Registration cancelled successfully', 'success');
            // Reload events data
            loadUpcomingEvents();
            loadUserEvents();
            loadUserStats();
        } else {
            showMessage(data.message || 'Failed to cancel registration', 'error');
        }
    } catch (error) {
        console.error('Error cancelling registration:', error);
        showMessage('An error occurred while cancelling your registration', 'error');
    }
}

// Initialize scroll reveal animations
function initScrollReveal() {
    const revealElements = document.querySelectorAll('.scroll-reveal');
    
    function checkScroll() {
        revealElements.forEach(element => {
            const rect = element.getBoundingClientRect();
            const windowHeight = window.innerHeight || document.documentElement.clientHeight;
            
            if (rect.top <= windowHeight * 0.8) {
                element.classList.add('visible');
            }
        });
    }
    
    // Check on initial load
    checkScroll();
    
    // Check on scroll
    window.addEventListener('scroll', checkScroll);
}

// Logout function
async function logout() {
    try {
        const response = await fetch('includes/logout.php');
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'index.html';
        } else {
            showMessage(data.message || 'Failed to logout', 'error');
        }
    } catch (error) {
        console.error('Logout failed:', error);
        showMessage('Logout failed. Please try again.', 'error');
    }
}

// Show message toast
function showMessage(message, type = 'error') {
    const messageDiv = document.getElementById('message');
    messageDiv.textContent = message;
    messageDiv.className = `message ${type}`;
    messageDiv.classList.remove('hidden');
    
    setTimeout(() => {
        messageDiv.classList.add('hidden');
    }, 3000);
} 