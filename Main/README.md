# Facial Recognition Attendance System

A comprehensive facial recognition-based attendance system designed to manage and track attendance for lectures efficiently. This system includes features for managing subjects, timetables, lectures, and student attendance.

## Table of Contents

- [Features](#features)
- [Technologies Used](#technologies-used)
- [Usage](#usage)

## Features

- **User Authentication**: Secure login and registration for users.
- **Facial Recognition**: Automatically recognizes and registers student attendance using facial recognition technology.
- **Timetable Management**: Schedule and manage lectures for different subjects.
- **Attendance Tracking**: Track and monitor student attendance for each lecture.
- **Dynamic Search**: Search for users and subjects dynamically.
- **Real-time Updates**: Real-time updates for chat and attendance information.
- **PDF Generation**: Generate PDF reports for attendance records with consistent styling.

## Technologies Used

- **Backend**: PHP, MongoDB
- **Frontend**: HTML, CSS, JavaScript, jQuery, TailwindCSS, Flowbite
- **Libraries and Frameworks**: Face Recognition libraries, DateTime, PDF generation libraries
- **Tools**: Composer for dependency management

## Usage

1. **User Registration and Login**:
   - Register a new user or login with existing credentials.
   - Users can be administrators or lecturers.

2. **Managing Subjects and Timetables**:
   - Administrators can add and manage subjects.
   - Lecturers can view their assigned subjects and manage lecture schedules.

3. **Facial Recognition Attendance**:
   - During lectures, the system uses facial recognition to automatically mark attendance.
   - Students must be enrolled in the subject to have their attendance recorded.

4. **Viewing Attendance Records**:
   - Lecturers and administrators can view and generate reports of attendance records.
   - Attendance rates are calculated and displayed as percentages.

5. **Chat System**:
   - Real-time chat system for communication between users.
   - Dynamic search and message functionalities.
