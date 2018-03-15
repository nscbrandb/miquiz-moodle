# MI-Quiz-Moodle-Plugin
Activity module for Moodle that enables you to use already existing Moodle 
structures like questions and courses to create quizzes in MI-Quiz.

## Install plugin in Moodle
TODO

## Development

This repository comes with a ready to use docker configuration to test the plugin (without the connection to MI Quiz).
1. Clone repository
2. Create `.env` file: `cp .env.exmaple .env`
3. Update `SERVER` variable in `.evn` file
4. If port is other than `8080` change host port in `docker-compose.yml`
5. Start containers with `docker-compose up -d`


# TODOs
- clean up code
- handle moodle questions with images