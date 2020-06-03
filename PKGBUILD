pkgname='caldav'
pkgver=1.0.0
pkgrel=1
pkgdesc='A ready to deploy CalDAV server based on SabreDAV'
arch=('any')
depends=('php' 'php-sqlite')
makedepends=('composer')
checkdepends=('bash-bats')

source=('phpunit.phar::https://phar.phpunit.de/phpunit-8.5.5.phar')
noextract=('phpunit.phar')

sha256sums=(
  'ae90e527a02b9acc913c0b52707aab5f33dc885e2a74f2f6ee6a771427e83942')

license=('custom')
url='https://github.com/ILadis/calendar'

build() {
  cd ..
  composer install
  bin/build
}

check() {
  cd ..
  php ./phpunit.phar --bootstrap vendor/autoload.php --testdox test/
  bats test/
}

package() {
  cd ..
  install -Dm 644 'calendar.phar' \
    "${pkgdir}/usr/lib/caldav/caldav.phar"

  mkdir -p \
    "${pkgdir}/usr/lib/systemd/system/" \
    "${pkgdir}/usr/lib/sysusers.d/" \
    "${pkgdir}/usr/lib/tmpfiles.d/"

  echo 'u caldav - - / /usr/bin/nologin' \
    > "${pkgdir}/usr/lib/sysusers.d/caldav.conf"

  echo 'd /var/lib/caldav 0750 caldav caldav -' \
    > "${pkgdir}/usr/lib/tmpfiles.d/caldav.conf"

  cat << EOF > "${pkgdir}/usr/lib/systemd/system/caldav.service"
[Unit]
Description=caldav
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=caldav
Group=caldav
WorkingDirectory=/var/lib/caldav
ExecStart=php -S localhost:6443 /usr/lib/caldav/caldav.phar
Restart=on-abort

[Install]
WantedBy=multi-user.target
EOF
}
