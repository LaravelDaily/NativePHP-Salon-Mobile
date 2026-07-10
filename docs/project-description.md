# Hair Salon Mobile app

Project Overview: Multi-Tenant Hair Salon Booking Platform

I am looking to develop a modern, scalable, multi-tenant hair salon booking platform. The ecosystem will consist of a centralized backend, a web administration panel for salon owners, and a customer-facing mobile application.

This brief specifically focuses on the customer-facing mobile application, which will allow clients to interact with salon, manage their profiles, and handle their appointments.

Technical Architecture & Requirements

Framework: NativePHP (Single codebase compiled for both Android and iOS).

Back-end: Separate Laravel app for API (will be created separately)

Authentication: Laravel Auth (Specifically SMS OTP for login and registration).

1. Authentication & Onboarding

User registration and secure login via SMS OTP (phone number verification).

2. Booking Engine

View salon services and real-time calendar availability.

Book new appointments.

Modify, reschedule, or cancel existing appointments.

3. Appointment Management

Appointment History: A dedicated dashboard to view past visits and upcoming bookings.

Notifications: Push notifications and automated reminders for upcoming appointments, cancellations, or rescheduling confirmations.

4. User Profile & Settings

Profile Page: Allow users to view and edit personal details (name, contact info, etc.).

Settings Page: Notification toggles, app preferences, and account deletion/logout options.

Design & Assets

UI/UX: Please refer to the mockups in the folder @docs/mockups and screenshots for the intended user flow, layout, and design language. Ensure the implementation closely matches the provided designs while maintaining responsive layouts across different screen sizes.
