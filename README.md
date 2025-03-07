# UserAuthService - Laravel user authentication service

This project showcases Laravel service class: `UserAuthService` designed for managing user registration, authentication, MFA and more.
Built with PHP 8.3 and Laravel 11, it shows advanced engineering practices, SOLID principles, and modern development techniques.

## File/Directory Structure

Below is the file and directory structure:

```
UserAuthService/
├── app/
│   ├── Services/
│   │   ├── UserAuthService.php
│   │   └── Notification/
│   │       └── EmailNotificationSender.php
│   ├── Contracts/
│   │   └── UserRepositoryInterface.php
│   ├── Repositories/
│   │   └── UserRepository.php
│   ├── Events/
│   │   └── UserRegistered.php
│   ├── Jobs/
│   │   └── SendVerificationEmail.php
│   ├── Exceptions/
│   │   └── RateLimitExceededException.php
├── tests/
│   ├── Unit/
│   │   └── Services/
│   │       └── UserAuthServiceTest.php
├── README.md
```

## Key Features Breakdown

1. **Advanced Caching Strategy**:
    - Uses Redis for caching user authentication data (`user:auth:{id}`) and MFA TOTP tokens (`mfa:token:{id}`).
    - Implements `Cache::store('redis')->remember` for efficient, TTL-based retrieval.

2. **Microservice Communication**:
    - Leverages Laravel Events (`UserRegistered`) and Queues (`SendVerificationEmail`) for asynchronous workflow.
    - Scalable design for integration with external services.

3. **Smart Adaptive Rate Limiting**:
    - Dynamically adjusts thresholds using `calculateDynamicThreshold` based on cached attempt history in Redis.
    - Prevents brute-force attacks with user-friendly feedback via `RateLimitExceededException`.

4. **Intelligent Error Handling**:
    - Integrates BugSnag for real-time error monitoring and structured logging for debugging.
    - Custom exceptions provide clear, actionable messages.

5. **Best Practices**:
    - Strict typing with `declare(strict_types=1)`.
    - Final classes to enforce composition over inheritance.
    - Dependency injection with `UserRepositoryInterface`.
    - Comprehensive Docblocks for maintainability.

6. **Security-First Approach**:
    - Secure password hashing with `Hash::make`.
    - MFA with TOTP and token expiration for enhanced protection.

7. **Event-Driven Architecture**:
    - Fires events and queues jobs for decoupled, extensible workflows.

## What I Find Interesting

### Highlights
1. **Adaptive Rate Limiting**:
    - I’m proud of the `enforceAdaptiveRateLimiting` method because it's a creative, simple and scalable solution. Adjusting thresholds based on historical attempts(cached in Redis) balances security and usability elegantly.
    - The algorithmic simplicity - reducing attempts by up to 4 based on history makes it both effective and lightweight.

2. **Advanced Caching**:
    - The Redis caching strategy in `authenticate` and `verifyMfa` optimizes performance without sacrificing security. It reduces database load while handling MFA securely with short-lived tokens.

3. **Error Handling with BugSnag**:
    - Integrating BugSnag for proactive monitoring and error handling.

4. **SOLID and Strict Typing**:
    - Adhering to SOLID principles with strict typing and final classes ensures a robust and testable codebase.

5. **Event-Driven Scalability**:
    - The use of events and queues fascinates me - it's a system that can grow effortlessly by adding new listeners or jobs, reflecting real-world scalability challenges.

6. **Security-First Engineering**:
    - Balancing security (MFA, rate limiting) with usability is a puzzle I love solving

7. **Performance Optimization**:
    - Exploring Redis's in-memory speed and queue-driven workflows excites me. It's about pushing the boundaries of what a service can handle under load.

8. **Testability**:
    - The clean separation of concerns and mocking in tests satisfy my curiosity about how systems behave in isolation. It's like dissecting the logic to understand its core.

## Setup Instructions
1. Ensure PHP 8.3 and Laravel 11 are installed.
2. Install dependencies: `composer require laravel/passport bugsnag/bugsnag-laravel otphp/otphp`.
3. Configure Redis in `.env` for caching and rate limiting.
4. Run tests: `php artisan test`.

## Contacts
- **Author**: Ned Andonov
- **Role**: Senior Software Engineer
- **Bio**: A passionate developer with over 20 years of experience in building fast, scalable and secure web applications.
- **Email**: Contact me at [neoplovdiv@gmail.com](mailto:neoplovdiv@gmail.com) for questions, feedback, or collaboration opportunities.