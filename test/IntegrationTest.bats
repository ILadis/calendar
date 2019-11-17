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
    'INSERT INTO users (id, username, password) VALUES (1, "user1", "$2y$10$59w3VWsmP0nKFDIU33RTU.PWuwlgp8WiL0O.yBtzracBKn4J7QQYy");'

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

  # assert
  [ "$statusCode" -eq 200 ]
  [ "$userCount" -eq 1 ]
}

@test 'execution of holidays task' {
  # arrange
  sqlite3 db.sqlite \
    'INSERT INTO users (id, username, password) VALUES (1, "user1", "$2y$10$59w3VWsmP0nKFDIU33RTU.PWuwlgp8WiL0O.yBtzracBKn4J7QQYy");' \
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

@test 'execution of movies task' {
  # arrange
  sqlite3 db.sqlite \
    'INSERT INTO users (id, username, password) VALUES (1, "user1", "$2y$10$59w3VWsmP0nKFDIU33RTU.PWuwlgp8WiL0O.yBtzracBKn4J7QQYy");' \
    'INSERT INTO calendars (id, components) VALUES (1, "VEVENT,VTODO");' \
    'INSERT INTO calendarinstances (id, calendarid, principaluri, uri) VALUES (1, 1, "principals/user1", "movies");'

  # act
  statusCode=$(curl -u 'user1:dummypwd' -w '%{http_code}' \
    --data-binary '{"month": "2019-11"}' \
    -so /dev/null 'http://localhost:8080/calendar/tasks/movies')
  eventCount=$(sqlite3 db.sqlite \
    'SELECT count(*) FROM calendarobjects;')
  changeCount=$(sqlite3 db.sqlite \
    'SELECT count(*) FROM calendarchanges;')

  # assert
  [ "$statusCode" -eq 200 ]
  [ "$eventCount" -ne 0 ]
  [ "$eventCount" -eq "$changeCount" ]
}

teardown() {
  kill $(jobs -p)
  rm -f db.sqlite
}

