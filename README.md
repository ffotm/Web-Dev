# **MasterEdu**  
*A Dynamic Web Platform for Training & Certification Management*

---

## Project Overview  
**MasterEdu** is a professional web platform that simplifies the management of training programs, certifications, and events. It features role-based dashboards, smart registration and cart systems, secure payment processing, automated certificate generation, and multi-user administration. The platform is inspired by professional platforms like FastLane.fr and BrainerX.com.

---

## Key Features  

### **Public Interface**  
- Animated and responsive homepage  
- Interactive training schedules  
- Partner and certification listings  
- Testimonials with ratings  
- Promotions with countdown timers  
- News and blog section  

### **Training Catalog & Registration**  
- Filter courses by certification, category, price, and rating  
- Detailed course pages with reviews  
- Immediate registration or add-to-cart functionality  

### **Cart & Payment System**  
- Dynamic participant management  
- Automatic price calculation with promotions  
- Cart auto-save  
- Secure bank transfer payments with commercial validation  
- Transaction history and receipts  

### **Event Management**  
- Event presentation and details  
- Online registration and participant management  
- Post-event evaluation  

---

## User Roles & Permissions  
The platform supports **8 distinct user roles** with personalized dashboards:

- **Visitor**: Browse catalog, register for events  
- **Learner**: Manage registrations, payments, certificates, profile, testimonials  
- **Commercial Assistant**: Validate payments, manage testimonials, internal messaging  
- **Commercial**: Manage training packs, private sessions, payment tracking  
- **Pedagogical Director**: Manage courses, schedules, certifications, trainers, surveys  
- **Marketing Manager**: Statistics, campaigns, events, newsletters  
- **Trainer**: Manage course content and materials  
- **Administrator**: Full system control (users, roles, content, logs, settings)  

---

## System Design & Architecture  

### UML Diagrams  
- **Use Case Diagrams**: Show actor-system interactions (browse, register, pay, validate, administer)  
- **Sequence Diagrams**: Detail temporal flows (registration, payment, validation, certificate generation)  
- **Class Diagram**: Illustrates system structure with entities (User, Courses, Sessions, Payments, Certifications) and relationships  

---

## Technology Stack  

### **Frontend**  
- HTML5, CSS3, JavaScript  
- jQuery  

### **Backend**  
- PHP 8  

### **Database**  
- MySQL 8.0  

### **Server & Tools**  
- Apache, XAMPP  
- VS Code  
- Git  

---

## Getting Started / Installation  

To run **MasterEdu** locally:  

1. **Install XAMPP** (Apache + MySQL) on your machine.  
2. **Start Apache and MySQL** from the XAMPP control panel.  
3. **Clone the repository** or copy the project files into the `htdocs` folder of XAMPP.  
4. **Import the database** using `phpMyAdmin` or via SQL script provided.  
5. **Configure database connection** in the PHP configuration file (`config.php` or similar).  
6. **Open your browser** and navigate to `http://localhost/<project-folder>` to access the platform.  

---

## Implementation Highlights  
- Multi-role architecture with 8 user profiles  
- Intelligent cart with dynamic pricing  
- Automated document generation (certificates, invoices)  
- Secure payment validation workflow  
- Responsive and interactive UI  

---

## Challenges Overcome  
- Coordinating a 3-person team  
- Managing complexity of 8 user roles  
- Integrating multiple modules (payment, events, notifications)  
- Performance optimization with large datasets  
- Debugging frontend-backend interactions  
- Meeting tight deadlines  
- Exhaustive testing of all user journeys  

---

## Future Improvements  
- Direct online card payments  
- Integrated video conferencing  
- Native mobile application  
- Intelligent course recommendation system  

---

## Documentation & References  
- PHP Official Documentation: https://www.php.net/docs.php  
- MySQL Documentation: https://dev.mysql.com/doc/  
- MDN Web Docs: https://developer.mozilla.org/  
- UML Documentation: https://www.uml.org/  
- FastLane.fr: https://fastlane.fr/  
- BrainerX.com: https://www.brainerx.com/  
- W3Schools: https://www.w3schools.com/  
- Stack Overflow: https://stackoverflow.com/  
- GitHub: https://github.com/  

---

**Academic Year:** 2025/2026  
**Institution:** Université Saad Dahleb Blida 1 - Faculty of Sciences, Computer Science Department  
**Program:** 3rd Year Cyber Security (Engineering)
