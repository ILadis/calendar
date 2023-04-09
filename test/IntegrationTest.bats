#!/usr/bin/env bats

setup() {
  # TODO check required commands to run tests
  # TODO define aliases for common tasks

  php -S 'localhost:8080' calendar.phar &
  curl -so /dev/null --retry 3 --retry-connrefused \
    'http://localhost:8080/calendar/'

  function passwd() {
    php -r "echo password_hash('$1', PASSWORD_BCRYPT);"
  }
}

@test 'login with pre existing user' {
  # arrange
  sqlite3 db.sqlite \
    "INSERT INTO users (id, username, password) VALUES (1, 'user1', '$(passwd "dummypwd")');" \
    "INSERT INTO principals (id, uri, displayname) VALUES (1, 'principals/user1', 'user1');"

  # act
  statusCode=$(curl -u 'user1:dummypwd' -w '%{http_code}' \
    'http://localhost:8080/calendar' -so /dev/null)

  # assert
  [ "$statusCode" -eq 200 ]
}

@test 'registration of new user' {
  # arrange
  sqlite3 db.sqlite \
    "INSERT INTO registrations (username) VALUES ('newuser');"

  # act
  statusCode=$(curl -u "newuser:dummypwd"  -w '%{http_code}' \
    -so /dev/null 'http://localhost:8080/calendar')
  userCount=$(sqlite3 db.sqlite \
    "SELECT count(*) FROM users WHERE username='newuser';")
  principalCount=$(sqlite3 db.sqlite \
    "SELECT count(*) FROM principals WHERE uri='principals/newuser';")

  # assert
  [ "$statusCode" -eq 200 ]
  [ "$userCount" -eq 1 ]
  [ "$principalCount" -eq 1 ]
}

@test 'execution of holidays task' {
  # arrange
  sqlite3 db.sqlite \
    "INSERT INTO users (id, username, password) VALUES (1, 'user1', '$(passwd "dummypwd")');" \
    "INSERT INTO principals (id, uri, displayname) VALUES (1, 'principals/user1', 'user1');" \
    "INSERT INTO calendars (id, components) VALUES (1, 'VEVENT,VTODO');" \
    "INSERT INTO calendarinstances (id, calendarid, principaluri, uri) VALUES (1, 1, 'principals/user1', 'holidays');"

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
    "INSERT INTO users (id, username, password) VALUES (1, 'user1', '$(passwd "dummypwd")');" \
    "INSERT INTO principals (id, uri, displayname) VALUES (1, 'principals/user1', 'user1');" \
    "INSERT INTO users (id, username, password) VALUES (2, 'newuser', '$(passwd "dummypwd")');" \
    "INSERT INTO principals (id, uri, displayname) VALUES (2, 'principals/newuser', 'newuser');"

  # act
  statusCode=$(curl -u 'user1:dummypwd' -w '%{http_code}' \
    --data-binary '{"principals": ["principals/user1", "principals/newuser"], "title": "Yet another TODO list", "uri": "yatodol"}' \
    -so /dev/null 'http://localhost:8080/calendar/todos')
  instanceCount=$(sqlite3 db.sqlite \
    "SELECT count(*) FROM calendarinstances WHERE access=3 AND displayname='Yet another TODO list';")

  # assert
  [ "$statusCode" -eq 201 ]
  [ "$instanceCount" -eq 2 ]
}

@test 'creation of calendar event' {
  # arrange
  sqlite3 db.sqlite \
    "INSERT INTO users (id, username, password) VALUES (1, 'user1', '$(passwd "dummypwd")');" \
    "INSERT INTO principals (id, uri, displayname) VALUES (1, 'principals/user1', 'user1');" \
    "INSERT INTO calendars (id, components) VALUES (1, 'VEVENT,VTODO');" \
    "INSERT INTO calendarinstances (id, calendarid, principaluri, uri) VALUES (1, 1, 'principals/user1', 'default');"

  event=`cat <<-EOF
		BEGIN:VCALENDAR
		VERSION:2.0
		PRODID:ical4j
		BEGIN:VEVENT
		DTSTAMP:20220111T091738Z
		UID:0efb20b9-2cd0-4a83-baf9-cbbf072c3c9f
		SEQUENCE:1
		SUMMARY:Sample Event
		DTSTART;TZID=Europe/Berlin:20211204T153000
		DTEND;TZID=Europe/Berlin:20211204T163000
		END:VEVENT
		END:VCALENDAR
		EOF`

  # act
  statusCode=$(curl -u 'user1:dummypwd' -w '%{http_code}' \
    -H 'Content-Type: text/calendar' --data-binary "$event" \
    -X 'PUT' -so /dev/null 'http://localhost:8080/calendar/calendars/user1/default/event.ics')

  # assert
  [ "$statusCode" -eq 201 ]
}

teardown() {
  kill $(jobs -p)
  rm -f db.sqlite
}

