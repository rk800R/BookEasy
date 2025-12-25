// script.js
// Iqtedar Mehdi
// Advanced search handler exposed globally so inline onclick works
async function handleSearch() {
    const location = document.getElementById('location')?.value || '';
    const checkIn = document.getElementById('check-in')?.value || '';
    const checkOut = document.getElementById('check-out')?.value || '';
    const guests = document.getElementById('guests')?.value || '';
    const resultsDiv = document.getElementById('results');

    if (!resultsDiv) { return; }

    // Basic validation
    if (!location || !checkIn || !checkOut || !guests || checkIn >= checkOut) {
        resultsDiv.innerHTML = '<p style="color: red; text-align: center;">Please ensure all fields are filled, and Check-out is after Check-in.</p>';
        return;
    }

    resultsDiv.innerHTML = `
        <h3 style="color: #4CAF50;">Search Parameters Confirmed!</h3>
        <p><strong>Destination:</strong> ${location}</p>
        <p><strong>Dates:</strong> ${checkIn} to ${checkOut}</p>
        <p><strong>Guests:</strong> ${guests}</p>
        <p style="color: #F96167; font-weight: bold; margin-top: 10px;">Searching database for results...</p>
    `;

    // Fetch available rooms from backend search
    try {
        const response = await fetch('rooms/rooms.php?action=searchRooms&query=' + encodeURIComponent(location));
        const data = await response.json();
        
        if (data.success && data.rooms.length > 0) {
            const roomsHtml = data.rooms.map(room => `
                <div style="background: rgba(255,255,255,0.9); padding: 15px; border-radius: 8px; margin: 10px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h4 style="margin: 0 0 8px 0; color: #F96167;">${room.name}</h4>
                    <p style="margin: 5px 0; color: #666;">${room.description}</p>
                    <p style="margin: 5px 0;"><strong>Price:</strong> ${Number(room.price).toLocaleString('en-PK')} PKR/Day</p>
                    <p style="margin: 5px 0; font-size: 14px; color: #666;">${room.amenities}</p>
                    <button onclick="window.location.href='rooms/RoomBrowsering.html'" style="margin-top: 10px; padding: 8px 15px; background: #F96167; color: white; border: none; border-radius: 6px; cursor: pointer;">View Details</button>
                </div>
            `).join('');
            
            resultsDiv.innerHTML = `
                <h3 style="color: #4CAF50;">Available Rooms (${data.rooms.length} found)</h3>
                <p><strong>Destination:</strong> ${location} | <strong>Dates:</strong> ${checkIn} to ${checkOut} | <strong>Guests:</strong> ${guests}</p>
                <div style="margin-top: 20px;">${roomsHtml}</div>
            `;
        } else {
            resultsDiv.innerHTML += `
                <div style="margin-top: 20px; padding: 20px; background: rgba(255,255,255,0.9); border-radius: 8px; text-align: center;">
                    <p style="color: #F96167; font-weight: bold;">No rooms found matching "${location}"</p>
                    <button onclick="window.location.href='rooms/RoomBrowsering.html'" style="margin-top: 15px; padding: 10px 20px; background: #F96167; color: white; border: none; border-radius: 6px; cursor: pointer;">Browse All Rooms</button>
                </div>
            `;
        }
    } catch (error) {
        console.error('Search error:', error);
        resultsDiv.innerHTML += `
            <div style="margin-top: 20px; padding: 20px; background: rgba(255,255,255,0.9); border-radius: 8px; text-align: center;">
                <p style="color: red;">Error loading rooms. Please try again.</p>
                <button onclick="window.location.href='rooms/RoomBrowsering.html'" style="margin-top: 15px; padding: 10px 20px; background: #F96167; color: white; border: none; border-radius: 6px; cursor: pointer;">Browse All Rooms</button>
            </div>
        `;
    }
}

// Make handler available for inline onclick
window.handleSearch = handleSearch;

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialise the dates for the Search Page (Explore.html)
    const checkInInput = document.getElementById('check-in');
    const checkOutInput = document.getElementById('check-out');

    if (checkInInput && checkOutInput) {
        // Set minimum date for Check-in to today
        const today = new Date().toISOString().split('T')[0];
        checkInInput.min = today;
        
        // Ensure Check-out date cannot be before Check-in date
        checkInInput.addEventListener('change', () => {
            checkOutInput.min = checkInInput.value;
            // If check-out is before the new check-in, reset check-out
            if (checkOutInput.value < checkInInput.value) {
                checkOutInput.value = '';
            }
        });
    }

    // 2. Wire up search button (in addition to inline onclick)
    const searchButton = document.querySelector('#search-form button');
    if (searchButton) {
        searchButton.addEventListener('click', handleSearch);
    }

    // 3. Contact Form Handler for Contact.html
    document.getElementById('contactForm')?.addEventListener('submit', function(event) {
        event.preventDefault(); // Stop form submission
        
        const name = document.getElementById('name').value;
        const messageElement = document.getElementById('contactMessage');
        
        messageElement.innerHTML = `✅ Thank you, **${name}**! Your message has been sent successfully. We will respond soon.`;
        messageElement.style.color = '#F96167';
        
        this.reset();
    });

    // 4. Feedback Form Handler for Report.html
    document.getElementById('feedbackForm')?.addEventListener('submit', function(event) {
        event.preventDefault(); // Stop form submission

        const type = document.getElementById('feedback-type').value;
        const messageElement = document.getElementById('feedbackMessage');
        
        const friendlyType = type.charAt(0).toUpperCase() + type.slice(1).replace('-', ' ');

        messageElement.innerHTML = `✅ Thank you for your **${friendlyType}**! Your report has been submitted.`;
        messageElement.style.color = '#4CAF50';
        
        this.reset();
    });
});