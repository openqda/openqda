# OpenQDA has been hacked at 27th December 2025

## Summary
Dear OpenQDA user, our server got attacked by a same-day-exploit on 26th December 2025.
We detected the hack and had to take the server offline for one day on 27th December 2025.
We applied necessary security patches to get the server back online.

However, after restoring all data from our daily backup, we still encounter some problems with some documents to be
displayed correctly.
If your uploaded documents show a spinning icon (converting) and do not show in the coding view, please be patient and
let us fix that problem.
**It is very unlikely that your project data is lost**, we just need to re-assign the contents appropriately.
Additionally, we are currently doing forensics to make sure no data has been stolen by the hackers.

Sorry for any inconvenience.

Your OpenQDA team. 

## FAQ

### I cannot open some or all of my project files. Is this data lost?
Very unlikely. We ran multiple backups per day to make sure there is no data loss.
However, we still encounter some encoding issues after backup restoration.
Please contact us via [openqda@uni-bremen.de](mailto:openqda@uni-bremen.de) if your projects are affected. 

### Has my data been leaked?
We currently have no hints, that the hack's purpose was to steal data.
However, further investigation is still pending. 

### Is my password compromised?
Unlikely, but with a grain of salt:
We do not store passwords in plain text format in our database.
Instead, we use bcrypt to hash our passwords, a common best practice to prevent password leaks.
**However, we advise you to be on the safe side and consider the passwords to be compromised.**
In consequence, you should change your OpenQDA password and also don't use the password on any other website,
especially in combination with the email address you used to register on OpenQDA.

### Where do I get more details and updates on the incident?
We will deliver detailed technical information on this incident at https://github.com/orgs/openqda/discussions/229
, once our investigation has been completed.
