## Character Page
A "Characters" page that grows as the reader progresses through the story

## Objective
One of the most frustrating things about reading a webcomic or other serialized story is when a character or location or item reappears after not having been mentioned for months or years and suddenly the reader is left either digging through source material to figure out who or what is being discussed, or (more often) simply accepting that they will be confused with the story for a bit, but hoping that context clues further on will remind them of what's going on. Serialized web stories almost always have a "characters" page - this can fill in the gaps to some extent, but almost always it's designed to be as spoiler free as possible, which means that the information you're looking for is rarely ever there.

"Sure the prince of the kingdom has a mysterious past, but the details of that mysterious past were discussed a few hundred pages back and why should I have to dig through history of the story to find the specific twenty pages that I need to re-read If those details cloud be summed up in an easily accessible paragraph or two?!"

The goal of this project is to create a "characters" page that updates as the story moves forward. It's designed specifically to determine what page is "current" based on the referrer URI (what page linked to it) and not show any data from after that point. Readers can browse backward through when a certain character or item's description has changed and be linked to the page within the source content relevant to that change. To be clear - the goal isn't to absolutely prevent users from spoiling content for themselves (because they're users - if they really want to, they'll find a way) but rather to offer them details relevant and up-to-date relative to their current position in the story.

## Getting Started
**Note:** This project does not include any process for uploading files to the server, so if you don't have a way to accomplish that, maybe this project is not right for you.

1. Upload all of the files for this project to the server. Ensure that they have the correct permissions, etc.
1. Review the **/includes/documentation.html** file to familiarize yourself with key details regarding the Character Page project.
1. Create a database and two database users for this project. See the "Mysql Users" section within **/includes/documentation.html** for more details
1. Edit the config directives within the includes/config.php file on the server to include the mysql username and password as well as other relevant bits.
1. Navigate to the site and enter the username and password for the admin user. This will create the necessary tables within the database
1. Navigate to the edit.php file and enter the username and password for the admin user. Add content (see the details below on how command keywords should be used).
1. That's it - You're up and running!
​
## Further details
View the file **/includes/documentation.html** for the full details and documentation on the Character Page project.
