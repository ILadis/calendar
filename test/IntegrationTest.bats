#!/usr/bin/env bats

setup() {
  # TODO check required commands to run tests
  # TODO define aliases for common tasks

  php -S 'localhost:8080' calendar.phar &
  curl -so /dev/null --retry 3 --retry-connrefused \
    'http://localhost:8080/calendar/'
}

@test 'login with pre existing user' {
  # arrange
  sqlite3 db.sqlite \
    'INSERT INTO users (id, username, password) VALUES (1, "user1", "$2y$10$59w3VWsmP0nKFDIU33RTU.PWuwlgp8WiL0O.yBtzracBKn4J7QQYy");' \
    'INSERT INTO principals (id, uri, displayname) VALUES (1, "principals/user1", "user1");'

  # act
  statusCode=$(curl -u 'user1:dummypwd' -w '%{http_code}' \
    'http://localhost:8080/calendar' -so /dev/null)

  # assert
  [ "$statusCode" -eq 200 ]
}

@test 'registration of new user' {
  # arrange
  sqlite3 db.sqlite \
    'INSERT INTO registrations (username) VALUES ("newuser");'

  # act
  statusCode=$(curl -u 'newuser:dummypwd' -w '%{http_code}' \
    -so /dev/null 'http://localhost:8080/calendar')
  userCount=$(sqlite3 db.sqlite \
    'SELECT count(*) FROM users WHERE username="newuser";')
  principalCount=$(sqlite3 db.sqlite \
    'SELECT count(*) FROM principals WHERE uri="principals/newuser";')

  # assert
  [ "$statusCode" -eq 200 ]
  [ "$userCount" -eq 1 ]
  [ "$principalCount" -eq 1 ]
}

@test 'execution of holidays task' {
  # arrange
  sqlite3 db.sqlite \
    'INSERT INTO users (id, username, password) VALUES (1, "user1", "$2y$10$59w3VWsmP0nKFDIU33RTU.PWuwlgp8WiL0O.yBtzracBKn4J7QQYy");' \
    'INSERT INTO principals (id, uri, displayname) VALUES (1, "principals/user1", "user1");' \
    'INSERT INTO calendars (id, components) VALUES (1, "VEVENT,VTODO");' \
    'INSERT INTO calendarinstances (id, calendarid, principaluri, uri) VALUES (1, 1, "principals/user1", "holidays");'

  # act
  statusCode=$(curl -u 'user1:dummypwd' -w '%{http_code}' \
    --data-binary '{"year": 2018, "state": "BY"}' \
    -so /dev/null 'http://localhost:8080/calendar/tasks/holidays')
  eventCount=$(sqlite3 db.sqlite \
    'SELECT count(*) FROM calendarobjects;')
  changeCount=$(sqlite3 db.sqlite \
    'SELECT count(*) FROM calendarchanges;')

  # assert
  [ "$statusCode" -eq 200 ]
  [ "$eventCount" -eq 15 ]
  [ "$eventCount" -eq "$changeCount" ]
}

@test 'creation of new shared todo list' {
  # arrange
  sqlite3 db.sqlite \
    'INSERT INTO users (id, username, password) VALUES (1, "user1", "$2y$10$59w3VWsmP0nKFDIU33RTU.PWuwlgp8WiL0O.yBtzracBKn4J7QQYy");' \
    'INSERT INTO principals (id, uri, displayname) VALUES (1, "principals/user1", "user1");' \
    'INSERT INTO users (id, username, password) VALUES (2, "newuser", "$2y$10$59w3VWsmP0nKFDIU33RTU.PWuwlgp8WiL0O.yBtzracBKn4J7QQYy");' \
    'INSERT INTO principals (id, uri, displayname) VALUES (2, "principals/newuser", "newuser");'

  # act
  statusCode=$(curl -u 'user1:dummypwd' -w '%{http_code}' \
    --data-binary '{"principals": ["principals/user1", "principals/newuser"], "title": "Yet another TODO list", "uri": "yatodol"}' \
    -so /dev/null 'http://localhost:8080/calendar/todos')
  instanceCount=$(sqlite3 db.sqlite \
    'SELECT count(*) FROM calendarinstances WHERE access=3 AND displayname="Yet another TODO list";')

  # assert
  [ "$statusCode" -eq 201 ]
  [ "$instanceCount" -eq 2 ]
}

teardown() {
  kill $(jobs -p)
  rm -f db.sqlite
}

