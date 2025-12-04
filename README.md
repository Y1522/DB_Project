# 📚 Library Management System

A full-stack web application for managing library operations with role-based access control, built with PHP, MySQL, and modern CSS.

## ✨ Features

### 🔐 Role-Based Access Control
- **Manager**: Full system access including user management
- **Staff**: Book management and member additions
- **Member**: Read-only access to catalog and personal fines

### 📖 Book Management
- Add, update, and delete books
- Real-time availability checking
- ISBN tracking and validation
- Multiple copies management
- Shelf location tracking

### 👥 User Management
- **Managers can**:
  - Add new members and staff
  - Manage all user accounts
  - View complete system analytics
  
- **Staff can**:
  - Add new members
  - Manage book inventory
  - Process borrowing transactions

### 📊 Analytics & Reports
- Most popular books by borrowing frequency
- Top authors analytics
- Most active members tracking
- Monthly borrowing summaries
- Fine management and tracking
- Overdue books monitoring

### 🎨 Modern UI
- Beautiful gradient purple/blue design
- Animated login page with floating effects
- Responsive card-based layout
- Smooth hover transitions and animations
- Color-coded alerts and notifications

## 🚀 Quick Start

### Prerequisites
- XAMPP (Apache + MySQL)
- PHP 7.4 or higher
- Modern web browser

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Y1522/DB_Project.git
   cd DB_Project
   ```

2. **Copy to XAMPP**
   ```bash
   # Copy project to XAMPP htdocs
   cp -r . C:/xampp/htdocs/library/
   ```

3. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start **Apache** and **MySQL**

4. **Import Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create database: `library_management`
   - Import `Library Management.sql`
   - Import `sample_data.sql` for test data

5. **Configure Database**
   - Edit `config.php` if needed (default: root with no password)

6. **Access Application**
   ```
   http://localhost/library/index.php
   ```

## 🔑 Default Login Credentials

### Manager (Full Access)
- **User ID**: 1
- **Name**: eng.el5oly
- **Role**: Manager

### Staff Members
- **User ID**: 2, 3, 4
- **Names**: Sarah, Mike, youssef
- **Role**: Staff

### Members
- **User ID**: 5-10
- **Names**: ahmed, adham, osama, mazen, sohieb, mohamed
- **Role**: Member

## 📁 Project Structure

```
DB_Project/
├── assets/
│   └── style.css          # Modern gradient UI styling
├── config.php             # Database configuration
├── db.php                 # Database connection handler
├── index.php              # Login page
├── dashboard.php          # Main application dashboard
├── logout.php             # Session logout handler
├── login.html             # Static login template
├── dashboard.html         # Static dashboard template
├── Library Management.sql # Full schema with database creation
├── schema_only.sql        # Schema without database creation
├── sample_data.sql        # Sample users, books, and categories
├── Data Dictionary_Library.pdf    # Database documentation
├── Erd.pdf                # Entity Relationship Diagram
└── Mapped Schema.pdf      # Database schema mapping
```

## 🗄️ Database Schema

### Tables
- **SYSTEM_USER**: Base user information
- **STAFF**: Staff-specific data
- **MEMBER**: Member-specific data
- **BOOKS**: Book inventory
- **AUTHORS**: Author information
- **CATEGORIES**: Book categories
- **PUBLISHERS**: Publisher information
- **BORROWING**: Borrowing transactions
- **FINE**: Fine management

## 🛠️ Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL 8.0
- **Frontend**: HTML5, CSS3
- **Server**: Apache (XAMPP)
- **Version Control**: Git

## 📊 Sample Data Included

- **10 Users** (1 Manager, 3 Staff, 6 Members)
- **3 Books** (1984, Pride and Prejudice, The Great Gatsby)
- **3 Authors** (George Orwell, Jane Austen, F. Scott Fitzgerald)
- **5 Categories** (Fiction, Classic, Science Fiction, Romance, CS)
- **3 Publishers** (Penguin Books, Scribner, HarperCollins)

## 🔄 Key Functionalities

### For Managers & Staff
```php
// Add new book
INSERT INTO BOOKS (Title, Author_ID, Category_ID, ...) VALUES (...)

// Update book availability
UPDATE BOOKS SET Copies_Available = ? WHERE Book_ID = ?

// Delete book (if not borrowed)
DELETE FROM BOOKS WHERE Book_ID = ? AND NOT IN (borrowed books)
```

### Advanced Queries
- Check if book is borrowed
- Verify member has unpaid fines
- View fines by date range
- Monthly borrowing statistics
- Most popular books/authors
- Most active members

## 🎯 Use Cases

1. **Library Staff**: Manage daily operations, track inventory
2. **Library Managers**: Oversee operations, add personnel, view analytics
3. **Library Members**: Browse catalog, check availability, view personal fines

## 🐛 Troubleshooting

### Connection Issues
- Verify MySQL is running in XAMPP
- Check `config.php` credentials
- Ensure `library_management` database exists

### Login Issues
- Verify sample data was imported
- Check user exists in `SYSTEM_USER` table
- Ensure role matches in `STAFF` or `MEMBER` table

### UI Issues
- Clear browser cache
- Verify `assets/style.css` exists
- Check Apache is serving files correctly

## 📝 License

This project is for educational purposes.

## 👥 Contributors

- **Y1522** - Initial development

## 🔗 Links

- [Repository](https://github.com/Y1522/DB_Project)
- [Issues](https://github.com/Y1522/DB_Project/issues)

## 📧 Contact

For questions or support, please open an issue on GitHub.

---

**Built with ❤️ for efficient library management**

