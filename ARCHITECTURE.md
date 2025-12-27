# TrackVault - System Architecture

## Overview

TrackVault is a production-ready, end-to-end data collection and payment management application built with:
- **Backend**: Laravel (PHP)
- **Frontend**: React Native (Expo) with TypeScript
- **Database**: SQLite (development) / MySQL/PostgreSQL (production)

## Architecture Principles

### Clean Architecture

The system follows Clean Architecture principles with clear separation of concerns:

```
┌─────────────────────────────────────────────┐
│           Presentation Layer                │
│   (Controllers, Views, UI Components)       │
├─────────────────────────────────────────────┤
│          Application Layer                  │
│   (Use Cases, Services, DTOs)              │
├─────────────────────────────────────────────┤
│            Domain Layer                     │
│   (Entities, Value Objects, Business Rules)│
├─────────────────────────────────────────────┤
│         Infrastructure Layer                │
│   (Database, External Services, Security)   │
└─────────────────────────────────────────────┘
```

### SOLID Principles

1. **Single Responsibility**: Each class has one reason to change
2. **Open/Closed**: Open for extension, closed for modification
3. **Liskov Substitution**: Subtypes must be substitutable for base types
4. **Interface Segregation**: Clients shouldn't depend on unused interfaces
5. **Dependency Inversion**: Depend on abstractions, not concretions

### DRY (Don't Repeat Yourself)

- Reusable components and services
- Shared utilities and helpers
- Common validation rules

### KISS (Keep It Simple, Stupid)

- Straightforward implementations
- Minimal complexity
- Clear, readable code

## Backend Architecture (Laravel)

### Directory Structure

```
backend/
├── app/
│   ├── Domain/              # Domain Layer
│   │   ├── Entities/       # Business entities
│   │   ├── ValueObjects/   # Value objects
│   │   ├── Repositories/   # Repository interfaces
│   │   └── Services/       # Domain services
│   ├── Application/         # Application Layer
│   │   ├── UseCases/       # Use case implementations
│   │   └── DTOs/           # Data Transfer Objects
│   ├── Infrastructure/      # Infrastructure Layer
│   │   ├── Persistence/    # Database implementations
│   │   └── Security/       # Security implementations
│   ├── Interfaces/          # Interface Layer
│   │   └── Http/
│   │       ├── Controllers/ # API Controllers
│   │       └── Middleware/  # Middleware
│   └── Models/             # Eloquent Models
├── database/
│   ├── migrations/         # Database migrations
│   └── seeders/           # Database seeders
└── routes/
    └── api.php            # API routes
```

### Core Entities

#### User
- Authentication and authorization
- Role-based access control (RBAC)
- Attribute-based access control (ABAC)
- JWT token management

#### Role & Permission
- Many-to-many relationship
- Flexible permission assignment
- Hierarchical role structure

#### Supplier
- Detailed supplier profiles
- Contact information
- Multi-unit quantity tracking
- Outstanding balance calculation

#### Product
- Product catalog management
- Multi-unit support
- Versioned rate management
- Historical rate preservation

#### ProductRate
- Time-based rate management
- Unit-specific rates
- Effective date ranges
- Historical preservation

#### Collection
- Daily collection recording
- Multi-unit quantity tracking
- Rate application at collection time
- Automatic total calculation

#### Payment
- Advance, partial, and final payments
- Payment type tracking
- Reference number management
- Automated calculations

#### AuditLog
- Comprehensive audit trail
- User action tracking
- Entity change history
- IP and user agent logging

### Database Schema

#### Key Features
- **Soft Deletes**: Preserve data integrity
- **Versioning**: Optimistic locking for concurrency
- **Timestamps**: Created/updated tracking
- **Foreign Keys**: Referential integrity
- **Indexes**: Performance optimization

### Security Features

#### Authentication
- JWT token-based authentication
- Secure password hashing (bcrypt)
- Token expiration and refresh

#### Authorization
- Role-Based Access Control (RBAC)
- Attribute-Based Access Control (ABAC)
- Permission-based route protection

#### Data Protection
- Encryption at rest and in transit
- HTTPS enforcement
- SQL injection prevention
- XSS protection
- CSRF protection

### API Design

#### RESTful Principles
- Resource-based URLs
- HTTP methods (GET, POST, PUT, DELETE)
- Stateless communication
- JSON responses

#### Response Format
```json
{
  "success": true,
  "data": {},
  "message": "Operation successful",
  "errors": []
}
```

### Multi-Unit Support

The system supports tracking quantities in multiple units:
- Kilograms (kg)
- Grams (g)
- Liters (l)
- Milliliters (ml)
- Units (pieces)

### Versioned Rate Management

- Historical rates preserved immutably
- New rates applied to new collections
- Rate history for auditing
- Time-based rate activation

### Payment Calculation

Automated payment calculations:
1. Sum all collections for a supplier
2. Apply appropriate rates per unit
3. Subtract all payments made
4. Calculate outstanding balance

### Concurrency Handling

- Optimistic locking with version field
- Server-side validation
- Conflict detection and resolution
- Atomic transactions

## Frontend Architecture (React Native/Expo)

### Directory Structure

```
frontend/
├── src/
│   ├── domain/              # Domain Layer
│   │   ├── entities/       # Business entities
│   │   └── repositories/   # Repository interfaces
│   ├── application/         # Application Layer
│   │   ├── useCases/       # Use case implementations
│   │   └── store/          # State management
│   ├── infrastructure/      # Infrastructure Layer
│   │   ├── api/            # API client
│   │   ├── storage/        # Local storage
│   │   └── sync/           # Sync mechanism
│   ├── presentation/        # Presentation Layer
│   │   ├── screens/        # Screen components
│   │   ├── components/     # Reusable components
│   │   └── navigation/     # Navigation config
│   └── shared/             # Shared utilities
│       ├── utils/          # Helper functions
│       └── constants/      # Constants
└── assets/                 # Static assets
```

### State Management

- Context API for global state
- Local component state for UI
- Persistent storage for offline data

### Offline Support

#### Local Persistence
- SQLite for structured data
- AsyncStorage for key-value pairs
- Secure storage for sensitive data

#### Sync Mechanism
- Queue pending operations
- Detect connectivity changes
- Sync when online
- Conflict resolution

### Multi-Device Support

- Centralized server state
- Version-based conflict detection
- Server-authoritative validation
- Consistent data across devices

## Data Integrity & Concurrency

### Multi-User Support

- Simultaneous access by multiple users
- Server-side conflict detection
- Optimistic locking with version field
- Transaction-based operations

### Data Validation

- Client-side validation (UX)
- Server-side validation (security)
- Type checking
- Business rule enforcement

### Audit Trail

- Complete history of all changes
- User action tracking
- Immutable audit logs
- Timestamp and IP logging

## Security Implementation

### Encryption

- **At Rest**: Database encryption
- **In Transit**: HTTPS/TLS
- **Storage**: Secure key storage

### Authentication Flow

1. User login with credentials
2. Server validates and issues JWT
3. Client stores token securely
4. Token included in API requests
5. Server validates token
6. Token refresh before expiration

### Authorization

- Check user roles
- Verify permissions
- Resource-level access control
- Operation-level restrictions

## Performance Optimization

- Database indexing
- Query optimization
- Caching strategies
- Lazy loading
- Pagination

## Scalability

- Horizontal scaling support
- Load balancing ready
- Database connection pooling
- Stateless API design

## Testing Strategy

- Unit tests for business logic
- Integration tests for API
- End-to-end tests for workflows
- Security testing
- Performance testing

## Deployment

### Backend
- Docker containerization
- Environment-based configuration
- Database migrations
- Automated deployments

### Frontend
- Expo EAS Build
- OTA updates
- Platform-specific builds
- App store deployment

## Monitoring & Logging

- Structured logging
- Error tracking
- Performance monitoring
- User analytics
- Audit log queries

## Use Case: Tea Leaves Collection

### Workflow

1. **Daily Collection**
   - Collector visits suppliers
   - Records quantities in kg/g
   - System applies current rate
   - Calculates total amount

2. **Payment Management**
   - Advance payments recorded
   - Partial payments tracked
   - Final settlement calculated

3. **Month-End Reconciliation**
   - Total collections calculated
   - Rates verified
   - Payments reconciled
   - Outstanding balance computed

4. **Multi-User Operations**
   - Multiple collectors work simultaneously
   - No data conflicts
   - Consistent calculations
   - Accurate records

## Future Enhancements

- Real-time notifications
- Advanced reporting & analytics
- Mobile app biometric authentication
- Integration with payment gateways
- Multi-language support
- Export to Excel/PDF

## Maintenance

- Regular security updates
- Database optimization
- Performance monitoring
- Backup and recovery
- Documentation updates
