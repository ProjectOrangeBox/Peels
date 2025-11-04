# ACL Package Overview

The `peels/acl` package implements an Access Control List (ACL) system that manages users, roles, and permissions. Everything lives under `src/` and is grouped into folders for models, entities, interfaces, and configuration.

## Root Classes

- `src/Acl.php`: Core service exposing methods to create and retrieve users, roles, and permissions. It wires together the configured models and enforces validation when instantiating entities.
- `src/User.php`: High-level helper that coordinates with the ACL service and session layer to track the authenticated user. It handles guest fallbacks and interacts with `SessionInterface`.

## Configuration

- `src/config/acl.php`: Default configuration for the ACL service (model class names, table references, etc.). Override these values to integrate with your application's persistence layer.
- `src/config/user.php`: Settings consumed by the `User` helper, including the guest user identifier and session key.

## Interfaces

Located in `src/interfaces/`:

- `AclInterface.php`: Contract for the ACL service.
- `UserInterface.php`: Contract for the session-aware user helper.
- `ModelInterface.php`: Shared base contract for data models.
- `UserModelInterface.php`, `RoleModelInterface.php`, `PermissionModelInterface.php`: Model-specific contracts.
- `UserEntityInterface.php`, `RoleEntityInterface.php`, `PermissionEntityInterface.php`: Contracts for value objects returned by the ACL service.

## Models

Located in `src/models/` and responsible for persistence and validation:

- `UserModel.php`: CRUD operations for users, including role assignment helpers.
- `UserMetaModel.php`: Manages auxiliary metadata associated with users.
- `RoleModel.php`: Handles role records and relationships to permissions.
- `PermissionModel.php`: Manages permission definitions and grouping.

## Entities

Located in `src/entities/` and encapsulate domain behaviour:

- `UserEntity.php`: Represents a user with role membership helpers and data hydration from the model layer.
- `RoleEntity.php`: Provides role metadata and exposes assigned permissions.
- `PermissionEntity.php`: Wraps individual permission records, including group descriptors.

## Exceptions

Located in `src/exceptions/`:

- `aclException.php`: Base exception for ACL-related errors.
- `RecordNotFoundException.php`: Thrown when requested records do not exist or cannot be loaded.

## Getting Started

1. Merge the configuration defaults with your application settings, pointing model classes at your own implementations if necessary.
2. Create the models' required database tables (users, roles, permissions, user meta, and pivot tables) aligning column names with the config.
3. Instantiate the ACL service with its dependencies (`PDO` and `ValidateInterface`) and consume it via the `AclInterface`.
4. Use the `User` helper to manage authenticated state in your session layer, relying on `UserInterface` for a consistent API.

Ensure your password storage is compatible with PHP's password hashing functions, and that the relationships between users, roles, and permissions are enforced at the database level for integrity.
