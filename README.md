![](tecart-logo-rgba_h120.png)
# Tecart Jira Connector Plugin

Der **Tecart Jira Connector** ist ein Plugin für das [Grav CMS](http://github.com/getgrav/grav). Angewendet wird er im Zusammenhang mit dem [TecArt® Skeleton Improval Workflow](https://github.com/TecArt/grav-skeleton-tecart-approval-workflow).

## TecArt® Skeleton Improval Workflow

TecArt® Approval Workflow ist ein Skeleton für das Flat-File CMS [Grav](http://github.com/getgrav/grav). Es beinhatet u.a. folgende Plugins:
- TecArt® Fork des Grav Plugin Admin
- TecArt® Fork des Grav Plugin GitSync
- **Grav Plugin TecArt® Jira Connector**
- Grav Plugin TecArt® Review workflow

## Installation

Laden Sie sich einfach die [ZIP-Datei des letzten Release](https://github.com/TecArt/grav-skeleton-tecart-approval-workflow/releases/download/1.0/grav-skeleton_tecart-approval-workflow_v1.0.zip) herunter, entpacken Sie sie in Ihrem web-root Verzeichnis und Sie können loslegen!

Webserver-Starten:
```bash
php -S localhost:8000 system/router.php
```

Hinweis: Bevor Sie den Content unter der Nutzung der TecArt Plugins bearbeiten können, müssen die Plugins *TecArt GitSync*, *TecArt Jira Connector* und *TecArt Review Workflow* konfiguriert werden. Außerdem sollten die Logins der eingerichteten Nutzer den jeweiligen Loginnamen Ihres Jira- bzw. Bitbucket-Systems entsprechen.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/tecart-jira-connector/tecart-jira-connector.yaml` to `user/config/plugins/tecart-jira-connector.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
jira_url: 'https://jira.yourdomain.com'
jira_project: PRO
jira_user: your-jira-login
jira_issue_status_ids:
  - '1'
  - '2'
  - '3'
  - '4'
jira_password: #######
```

Note that if you use the admin plugin, a file with your configuration, and named tecart-jira-connector.yaml will be saved in the `user/config/plugins/` folder once the configuration is saved in the admin.

**Kontakt**  
TecArt GmbH  
Sören Müller  
github@tecart.de
