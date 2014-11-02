# copy sources.list
cp -f /vagrant/vagrant/sources.list /etc/apt/

# set mysql password so it isn't prompted during installation
debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password password rootpass'
debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password_again password rootpass'

# update package lists
apt-get update

# install mysql, nginx, php5-fpm
apt-get install -y mysql-server mysql-client nginx php5-fpm php5-mysql php5-curl php5-gd php5-intl php-pear php5-imagick php5-imap php5-mcrypt php5-memcached

# copy nginx config
cp -f /vagrant/vagrant/default /etc/nginx/sites-available

# remove default html directory
rm -Rf /usr/share/nginx/html

# mount www-root
ln -s /vagrant/ /usr/share/nginx/html

# restart services
service nginx restart
service php5-fpm restart
