Open Task Scheduler.
Choose Create Basic Task.
Name it something like Library Inactivity Check.
Set the trigger to Daily.
Set the time you want it to run, for example 2:00 AM.
Choose Start a Program.
Program/script: php.exe
Add arguments: "C:\xampp\htdocs\Library Management System\manage-inactivity.php"
Start in: C:\xampp\htdocs\Library Management System
Finish, then open the task properties and enable: