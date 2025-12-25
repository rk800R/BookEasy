// rooms.js - Frontend JavaScript for Room Management

class RoomManager {
    constructor() {
        this.apiUrl = 'rooms.php';
        this.modal = document.getElementById("roomModal");
        this.modalImg = document.getElementById("modal-img");
        this.modalTitle = document.getElementById("modal-title");
        this.modalDesc = document.getElementById("modal-description");
        this.modalPrice = document.getElementById("modal-price");
        this.modalExtra = document.getElementById("modal-extra");
        this.currentRoomId = null;
        
        this.init();
    }

    init() {
        this.checkForSearchQuery();
        this.loadRooms();
        this.setupEventListeners();
        this.setupSearch();
    }

    setupEventListeners() {
        // Close modal
        document.querySelector(".close").onclick = () => this.closeModal();
        
        window.onclick = (e) => {
            if (e.target == this.modal) this.closeModal();
        };

        // Book room button
        document.getElementById("bookRoomBtn").addEventListener("click", () => {
            this.bookRoom();
        });
    }

    checkForSearchQuery() {
        const query = sessionStorage.getItem('searchQuery');
        if (query) {
            const searchBox = document.getElementById('search-box');
            if (searchBox) {
                searchBox.value = query;
                // Perform search after page loads
                setTimeout(() => this.searchRooms(query), 100);
            }
            sessionStorage.removeItem('searchQuery');
        }
    }

    setupSearch() {
        const searchForm = document.querySelector('.top-search-bar form');
        const searchBox = document.getElementById('search-box');

        if (searchForm && searchBox) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const searchTerm = searchBox.value.trim();
                if (searchTerm) {
                    this.searchRooms(searchTerm);
                } else {
                    this.loadRooms();
                }
            });
        }
    }

    async loadRooms() {
        try {
            const response = await fetch(`${this.apiUrl}?action=getAllRooms`);
            const data = await response.json();

            if (data.success) {
                this.displayRooms(data.rooms);
                if (data.usedFallback || data.dbConnected === false) {
                    this.showNotification('Showing fallback rooms. Check database connection.', 'error');
                }
            } else {
                console.error('Error loading rooms:', data.message);
                this.showNotification('Error loading rooms', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Failed to load rooms', 'error');
        }
    }

    async searchRooms(searchTerm) {
        try {
            const response = await fetch(`${this.apiUrl}?action=searchRooms&query=${encodeURIComponent(searchTerm)}`);
            const data = await response.json();

            if (data.success) {
                this.displayRooms(data.rooms);
                if (data.usedFallback || data.dbConnected === false) {
                    this.showNotification('Showing fallback rooms. Check database connection.', 'error');
                }
                if (data.rooms.length === 0) {
                    this.showNotification('No rooms found matching your search', 'info');
                }
            } else {
                console.error('Error searching rooms:', data.message);
                this.showNotification('Error searching rooms', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Failed to search rooms', 'error');
        }
    }

    async getRoomDetails(roomId) {
        try {
            const response = await fetch(`${this.apiUrl}?action=getRoomDetails&id=${roomId}`);
            const data = await response.json();

            if (data.success) {
                return data.room;
            } else {
                console.error('Error getting room details:', data.message);
                return null;
            }
        } catch (error) {
            console.error('Error:', error);
            return null;
        }
    }

    displayRooms(rooms) {
        const grid = document.querySelector('.room-grid');
        grid.innerHTML = ''; // Clear existing rooms

        rooms.forEach(room => {
            const roomDiv = this.createRoomElement(room);
            grid.appendChild(roomDiv);
        });

        // Re-attach event listeners to new buttons
        this.attachRoomButtonListeners();
    }

    createRoomElement(room) {
        const div = document.createElement('div');
        div.className = 'room-listing';
        div.dataset.roomId = room.id;

        div.innerHTML = `
            <img src="${room.image_url}" alt="${room.name}">
            <p>
                <strong>${room.name}</strong><br>
                <small style="color: gray;">${room.description}</small><br>
                <strong>${this.formatPrice(room.price)} PKR/Day</strong><br><br>
                <span class="extra-info" style="display:none;">
                    ${room.amenities || 'No amenities listed'}
                </span>
                <button class="room-button" data-room-id="${room.id}">View Details</button>
            </p>
        `;

        return div;
    }

    attachRoomButtonListeners() {
        document.querySelectorAll(".room-button").forEach(button => {
            button.addEventListener("click", (e) => {
                const roomId = e.target.dataset.roomId;
                this.showRoomModal(roomId);
            });
        });
    }

    async showRoomModal(roomId) {
        const room = document.querySelector(`[data-room-id="${roomId}"]`).closest('.room-listing');

        this.modalImg.src = room.querySelector("img").src;
        this.modalTitle.innerText = room.querySelector("strong").innerText;
        this.modalDesc.innerText = room.querySelector("small").innerText;
        this.modalPrice.innerText = room.querySelectorAll("strong")[1].innerText;
        this.modalExtra.innerText = room.querySelector(".extra-info").innerText;
        
        this.currentRoomId = roomId;
        this.modal.style.display = "block";
    }

    closeModal() {
        this.modal.style.display = "none";
        this.currentRoomId = null;
    }

    bookRoom() {
        const priceText = this.modalPrice.innerText;
        const priceMatch = priceText.match(/[\d,]+/);
        const priceValue = priceMatch ? parseFloat(priceMatch[0].replace(/,/g, '')) : 0;
        
        const roomData = {
            id: this.currentRoomId || Date.now(),
            name: this.modalTitle.innerText,
            description: this.modalDesc.innerText,
            price: priceValue,
            image: this.modalImg.src,
            amenities: this.modalExtra.innerText
        };

        // Save room data to sessionStorage
        sessionStorage.setItem('bookingRoom', JSON.stringify(roomData));

        // Log booking attempt (optional - can be sent to backend)
        this.logBooking(roomData);

        // Redirect to checkout page
        window.location.href = '../Checkout.html';
    }

    async logBooking(roomData) {
        try {
            await fetch(`${this.apiUrl}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'logBooking',
                    roomId: roomData.id,
                    roomName: roomData.name
                })
            });
        } catch (error) {
            console.error('Error logging booking:', error);
        }
    }

    formatPrice(price) {
        return Number(price).toLocaleString('en-PK');
    }

    showNotification(message, type = 'info') {
        // Simple notification system
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background-color: ${type === 'error' ? '#F96167' : type === 'success' ? '#4CAF50' : '#2196F3'};
            color: white;
            border-radius: 6px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialize room manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Check if we should use backend or keep localStorage fallback
    const useBackend = true; // Set to false to use original localStorage method

    if (useBackend) {
        window.roomManager = new RoomManager();
    } else {
        // Original localStorage-based functionality is preserved in HTML
        console.log('Using localStorage-based room management');
    }
});

// Add CSS animations for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
