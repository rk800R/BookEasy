# BookEasy - Hotel Room Booking System

## 📋 Overview

BookEasy is a comprehensive hotel and accommodation booking platform designed to simplify the reservation process for travelers. Built with modern web technologies, it provides a seamless experience for browsing, comparing, and booking accommodations across Pakistan.

## ✨ Features

### User Features
- 🔍 **Advanced Search & Filtering** - Search hotels by location, price, dates, and amenities
- 🏨 **Room Browsing** - Detailed room information with high-quality images and descriptions
- 📅 **Real-time Availability** - Check room availability for your desired dates
- 💳 **Secure Booking** - Safe payment processing with payment summaries
- 👤 **User Accounts** - Create accounts, manage profiles, and booking history
- ⭐ **Ratings & Reviews** - View customer feedback and leave ratings
- 📧 **Email Notifications** - Receive booking confirmations and updates

### Admin Features
- 🛠️ **Room Management** - Add, edit, and delete hotel rooms
- 👥 **User Management** - Manage user accounts and permissions
- 📊 **Booking Dashboard** - Track all bookings and reservations
- 💬 **Feedback Management** - View and respond to customer feedback
- 📈 **Analytics** - Monitor platform activity and bookings

## 🛠️ Tech Stack

**Frontend:**
- HTML5
- CSS3
- JavaScript (Vanilla JS)
- Responsive Design

**Backend:**
- PHP (Server-side logic)
- MySQL (Database)

**Architecture:**
- MVC Pattern
- RESTful API Principles

## 📁 Project Structure

```
WEB project/
├── index.html                # Home page
├── Project.html              # Main project file
├── Login.html                # User login
├── Register.html             # User registration
├── Search.html               # Room search
├── About.html                # About page
├── Contact.html              # Contact page
├── Profile.html              # User profile
├── Checkout.html             # Booking checkout
├── current_bookings.html     # Active bookings
├── old_bookings.html         # Booking history
├── Report.html               # Reports page
├── css/                      # Stylesheets
│   └── style.css
├── api/                      # Backend API endpoints
│   ├── bookings.php
│   ├── contact.php
│   └── ...
├── admin_control/            # Admin panel
│   ├── AdminControl.html
│   ├── admin_login.php
│   └── admin_login.js
├── rooms/                    # Room management
│   ├── RoomBrowsering.html
│   ├── rooms.php
│   └── rooms.js
├── users/                    # User management
│   ├── users.php
│   └── users.js
├── report/                   # Reports module
│   ├── Report.html
│   ├── report.php
│   └── report.js
├── media/                    # Images & media files
└── script.js                 # Main JavaScript
```

## 🚀 Getting Started

### Prerequisites
- XAMPP (PHP 7.0+, MySQL 5.6+)
- Web Browser (Chrome, Firefox, Safari, Edge)
- Git (for cloning)

### Installation

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/rk800R/Bookeasy.git
   ```

2. **Move to XAMPP htdocs:**
   ```bash
   Move the project folder to D:\Softwares\XAMPP\htdocs\
   ```

3. **Start XAMPP:**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

4. **Create Database:**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `bookeasy`
   - Import database schema (if available)

5. **Configure Database Connection:**
   - Update database credentials in your PHP files if needed

6. **Access the Application:**
   ```
   http://localhost/Bookeasy/
   ```

## 👤 User Roles

### Guest User
- Browse hotels and rooms
- View amenities and pricing
- Create an account

### Registered User
- Create and manage bookings
- View booking history
- Rate and review hotels
- Manage profile information
- Receive email notifications

### Admin User
- Manage hotel rooms and availability
- Manage user accounts
- View all bookings
- Manage customer feedback
- Access analytics dashboard

## 🔐 Security Features

- Password hashing
- SQL injection prevention (prepared statements)
- CSRF token validation
- Session management
- Input validation and sanitization

## 📝 Usage Examples

### For Users:
1. Register or login to your account
2. Search for hotels by location and dates
3. View room details and amenities
4. Add rooms to booking cart
5. Complete payment process
6. View confirmation and booking details

### For Admins:
1. Login to admin panel
2. Manage room inventory
3. Monitor bookings in real-time
4. Respond to customer feedback
5. View platform analytics

## 🐛 Known Issues

- Currently supports Pakistan locations only
- Mobile app not available yet

## 🚧 Future Enhancements

- [ ] Mobile application (iOS & Android)
- [ ] International hotel listings
- [ ] Advanced payment gateway integration
- [ ] Multiple language support
- [ ] AI-powered recommendations
- [ ] Real-time chat support
- [ ] Loyalty rewards program

## 📧 Contact & Support

- **Author:** rk800R
- **GitHub:** [GitHub Profile](https://github.com/rk800R)
- **Repository:** [BookEasy](https://github.com/rk800R/Bookeasy)

## 📄 License

This project is licensed under the MIT License.

## 🙏 Acknowledgments

- XAMPP for local development environment
- PHP and MySQL communities
- Contributors and testers

---

**Last Updated:** December 26, 2025

**Status:** ✅ Active Development
