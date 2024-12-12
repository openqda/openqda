# Settings API Documentation

Update on 13.11.2024: at this point in time we don't validate settings with the allowed settings table, so the allowed settings table is not used at all. This means that the settings table can have any key-value pairs.

## Overview
This API manages settings for different entities (users, projects, codebooks). Settings are grouped and can have multiple key-value pairs.

## Base URL
All endpoints are prefixed with `/settings`

## Data Structure
Each setting record has this structure:

```json
   {
       "id": "uuid-string",
       "model_type": "user|project|codebook",
       "model_id": "id-string",             // Can be UUID or numeric
       "values": {
           "group_name": {                   // e.g., "display", "notifications"
               "setting_key": "value"        // e.g., "theme": "dark"
           }
       }
   }
```

This means that the settings table has a `model_type` and `model_id` column to associate settings with different entities.
Note: the entity of a setting (project, user, code... ) is defined on creation and never changed again. Afterwards, the setting can only be updated.


## Endpoints

### List Settings
GET `/settings`

Query Parameters:
- `model_type`: Filter by type (optional)
- `model_id`: Filter by ID (optional)

### Create Setting
POST `/settings`

Request body:
```json
   {
       "model_type": "project",
       "model_id": "123",
       "values": {
           "display": {
               "theme": "dark",
               "sidebar": "expanded"
           },
           "notifications": {
               "email": true,
               "push": false
           }
       }
   }
```

### Update Setting

This is used when changing the whole settings object. If you want to update a single value, use the PATCH endpoint.

PUT `/settings/{id}`

Request body:
```json
   {
       "values": {
           "display": {
               "theme": "light"
           }
       }
   }
```

### Update Single Value
PATCH `/settings/{id}/value`

Request body:

   {
       "group": "display",
       "key": "theme",
       "value": "light"
   }


### Delete Setting
DELETE `/settings/{id}`

## Response Codes
- 200: Success
- 201: Created successfully
- 403: Unauthorized action
- 404: Setting not found
- 422: Validation error

## Authorization Rules
- Users can only modify their own user settings
- Project settings require user to be in project or team
- All requests require authentication


# CLI Settings Management

For developers managing settings via command line, the following commands are available:

## Command Structure
```bash
php artisan settings:manage {action} [options]
Available Actions
1. Create Allowed Setting Value
Creates a new allowed value for a specific setting key.
```