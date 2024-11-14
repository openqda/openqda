# Current Tests
Last updated: 2024-11-14 11:44:56
## Api Token Permissions
    - api token permissions can be updated

## Authentication
    - login screen can be rendered
    - users can authenticate using the login screen
    - users can not authenticate with invalid password

## Browser Sessions
    - other browser sessions can be logged out

## Code Controller
    - destroy code successfully
    - destroy code unauthorized
    - update code color
    - update code title
    - update code description
    - create code with valid parent
    - prevent self referential code creation
    - prevent self referential code on create

## Codebook Controller
    - store codebook successfully
    - store codebook validation failure
    - store codebook unauthorized
    - destroy codebook successfully
    - destroy codebook unsuccessfully
    - update code order successfully
    - update code order invalid data

## Create Api Token
    - api tokens can be created

## Create Team
    - teams can be created

## Delete Account
    - user accounts can be deleted

## Delete Api Token
    - api tokens can be deleted

## Delete Team
    - teams cant be deleted
    - empty teams can be deleted
    - personal teams cant be deleted

## Email Verification
    - email verification screen can be rendered
    - email can be verified
    - email can not verified with invalid hash

## Example
    - the application returns a successful response

## Invite Team Member
    - team members can be invited to team
    - team member invitations can be cancelled

## Leave Team
    - users can leave teams
    - team owners cant leave their own team

## Password Confirmation
    - confirm password screen can be rendered
    - password can be confirmed
    - password is not confirmed with invalid password

## Password Reset
    - reset password link screen can be rendered
    - reset password link can be requested
    - reset password screen can be rendered
    - password can be reset with valid token

## Profile Information
    - profile information can be updated

## Project Controller
    - index displays projects
    - store creates project
    - store fails validation
    - update project attributes
    - destroy deletes project

## Registration
    - registration screen can be rendered
    - new users can register

## Remove Team Member
    - team members can be removed from teams
    - team owner cannot be removed by team members
    - admin can remove other team members

## Settings Controller
    - user can view settings list
    - user can create setting for project in team
    - user cannot create setting for project not in team
    - user can create own settings
    - user cannot create settings for other user
    - user can update project setting in team
    - can delete setting with proper permissions

## Source Controller
    - index displays project sources
    - store creates new source
    - store validates file type
    - lock and code successfully locks source
    - unlock source successfully
    - update source content
    - rename source
    - destroy source
    - fetch document
    - download source
    - unauthorized user cannot access source

## Two Factor Authentication Settings
    - two factor authentication can be enabled
    - recovery codes can be regenerated
    - two factor authentication can be disabled

## Update Password
    - password can be updated
    - current password must be correct
    - new passwords must match

## Update Team Member Role
    - only team owner can update team member roles

## Update Team Name
    - team names can be updated

