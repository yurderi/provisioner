# Provisioner

Yet this project is used to create apache2 vhost automatically based on a YAML-configuration.

### Installation
```
# Clone the project
git clone https://github.com/yurderi/provisioner.git

# Symlink executable
ln -s /path/to/provisioner/bin/console /usr/bin/yp
```

### Requirements
- apache2
- certbot

### Usage
```
yp run apache <filename>
```

### Example
```yaml
default:
  # Whether the vhost is the default vhost
  default: false
  # The path where the html files are located
  root: ""
  # Whether the host is enabled or not
  active: true
  # Enable ssl (using certbot --apache)
  ssl: true
  # Rules for the directory directive
  rules:
    - AllowOverride All
  # Additional hosts the website should be available through
  alias: []

hosts:
  www.yurderi.de:
    default: true
    root: /var/www/www.yurderi.de
```