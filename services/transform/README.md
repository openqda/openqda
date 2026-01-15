# Transform Services

Until specified otherwise by a future holistic plugin/service spec,
the current defined role of transform services is to take a file
(in any REFI-supported format) and transforms it into a new format.

Check the README files in each directory to understand their purpose 
and behaviour.

## Transform Protocol Specification

This protocol is designed to standardize how transform services operate within the REFI ecosystem.
It is language agnostic and can be implemented in many programming languages or frameworks.
Any transform service must implement this protocol.

### Levels of provision
- core level - A core transform service is one that is registered with the system's Service Registry and is avialable to all users.
- project level - A project level transform service is one that is registered within a specific project and is only available to team-members of that project.
- individual level - An individual level transform service is one that is registered to a specific user and is only available to that user.

### 1. Service Registration
Each "core" transform service must be registered with the system's `ServiceRegistry`.

The service must provide metadata including:
- Service Name
- Supported Input Formats
- Supported Output Formats
- Service Endpoint URL
- Contact Information
- Version
- Description
- License Information
- Terms of Use
- Privacy Policy
- Authentication method

- The service must also implement health check endpoints to ensure availability.
- The service must implement authentication and authorization mechanisms as per system requirements.
- The service must log all requests and responses for auditing purposes.


### 2. Service Interface
The service must expose a RESTful API with the following endpoints:
- 
1. Accept a file input in any REFI-supported format.
2. Process the file and convert it into a new specified format.
3. Return the transformed file to the requester.

