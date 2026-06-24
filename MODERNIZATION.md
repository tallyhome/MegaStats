# MegaStats — Modernization Specification

You are a senior PHP 8.2, Linux System Administration, DevOps and UI/UX engineer.

I have a legacy server monitoring application called MegaStats. It currently works but is based on very old PHP code and outdated HTML.

Your mission is to modernize the application while preserving all existing functionality.

## Requirements

### PHP Modernization

- Upgrade all code to PHP 8.2 compatibility.
- Eliminate ALL warnings, notices and deprecated behavior.
- Enable full E_ALL error reporting with zero warnings.
- Replace unsafe code patterns.
- Initialize variables properly.
- Fix undefined array key warnings.
- Use strict comparisons where appropriate.
- Improve code readability and maintainability.
- Refactor duplicated code.

### Security

- Add a secure login page.
- Implement session authentication.
- Add logout functionality.
- Protect all pages from unauthorized access.
- Add CSRF protection for forms.
- Sanitize all user input.
- Escape all output.
- Prevent command injection.
- Prevent XSS vulnerabilities.
- Prevent directory traversal attacks.

### Access Control

Create configuration options for:

- Password authentication
- IP whitelist
- Password + IP whitelist combined
- Admin session timeout

Store settings in a separate config file.

### User Interface

Completely redesign the interface using:

- Bootstrap 5
- Responsive layout
- Mobile friendly design
- Modern cards
- Dark mode
- Light mode
- Automatic theme switching
- Professional admin dashboard appearance

### Dashboard

Create a clean monitoring dashboard with:

- CPU usage
- RAM usage
- Swap usage
- Load averages
- Disk usage
- Network traffic
- Server uptime
- Current users
- Running services
- MySQL/MariaDB status

Use visual cards and status indicators.

### Charts

Add Chart.js graphs for:

- CPU history
- Memory history
- Disk usage
- Network traffic
- vnStat traffic
- System load

Graphs should update automatically.

### Monitoring

Add monitoring for:

- Apache
- Nginx
- MariaDB
- MySQL
- Exim
- SSH
- Pure-FTPD
- cPanel services
- PHP-FPM pools

Display green/red status indicators.

### Disk Monitoring

Show:

- Mounted partitions
- Used space
- Free space
- Usage percentages
- Warning thresholds

Add visual progress bars.

### Network Monitoring

Improve vnStat integration.

Support:

- KiB
- MiB
- GiB
- TiB

Handle all modern vnStat output formats safely.

Display:

- Today
- Yesterday
- Current month
- Previous month
- Total traffic

### Alerts

Add configurable thresholds for:

- CPU usage
- RAM usage
- Disk usage
- Load average

Display warning and critical states.

### Logging

Add:

- Application log file
- Authentication log
- Error log
- Activity log

### Configuration

Create a clean configuration system:

```
config/
  app.php
  monitoring.php
  security.php
```

Avoid hardcoded values.

### Project Structure

Refactor into:

```
/config
/includes
/classes
/templates
/assets/css
/assets/js
/assets/images
```

Keep code organized.

### Compatibility

Target:

- AlmaLinux 9
- Rocky Linux 9
- cPanel/WHM
- PHP 8.2+
- Apache 2.4

### Performance

- Reduce shell command execution where possible.
- Cache expensive operations.
- Optimize dashboard loading speed.

## Deliverables

1. Fully upgraded PHP 8.2 codebase.
2. Modern Bootstrap 5 interface.
3. Authentication system.
4. Dark mode.
5. Chart.js dashboard.
6. Security improvements.
7. Documentation and installation guide.

Maintain all existing MegaStats functionality while making the application look and feel like a modern professional server monitoring panel.
