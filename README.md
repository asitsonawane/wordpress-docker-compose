
# Setting Up and Deploying WordPress with Docker Compose and GitHub Actions

This guide will walk you through the process of setting up a local development environment for WordPress using Docker Compose, and then deploying the website to a hosting platform using GitHub Actions. This allows for easy development and deployment without worrying about complex configuration.



## Introduction

WordPress is a free and open-source Content Management System (CMS) built on a MySQL database with PHP processing. Thanks to its extensible plugin architecture and templating system, most of its administration can be done through the web interface. This is a reason why WordPress is a popular choice when creating different types of websites, from blogs to product pages to eCommerce sites.

Running this WordPress project involves installing a LEMP (Linux, Nginx, MySQL, and PHP) stack, which can be time-consuming. However, by using tools like Docker and Docker Compose and Github Actions, you can streamline the process of setting up your preferred stack and installing WordPress. Instead of installing individual components by hand, you can use images, which standardize things like libraries, configuration files, and environment variables. Then, run these images in containers, isolated processes that run on a shared operating system. Additionally, by using Compose, you can coordinate multiple containers — for example, an application and database — to communicate with one another.

## Docker Compose and Project architecture

![Docker Compose and Project architecture](https://raw.githubusercontent.com/asitsonawane/wordpress-docker-compose/main/wordpress-docker-compose.jpg)
## Tech Stack

**Client:** Wordpress, PHP, HTML, CSS, JavaScript

**Server:** Ubuntu 22, Linux, Nginx, YAML

**Tools:** Docker, Docker-Compose, Cloudflare, DigitalOcean Firewall, Git, Github Actions



## Initial Server Setup with Ubuntu 22.04

To setup this project, you will need:

### Step 1 — Logging in as root (server setup)

A server running Ubuntu 22.04, along with a non-root user with sudo privileges and an active firewall.

To log into your server, you will need to know your server’s public IP address. You will also need the password or — if you installed an SSH key for authentication — the private key for the root user’s account.

If you are not already connected to your server, log in now as the root user using the following command (substitute the highlighted portion of the command with your server’s public IP address):

```bash
  ssh root@your_server_ip
```

Accept the warning about host authenticity if it appears. If you are using password authentication, provide your root password to log in. If you are using an SSH key that is passphrase protected, you may be prompted to enter the passphrase the first time you use the key each session. If this is your first time logging into the server with a password, you may also be prompted to change the root password.

### Step 2 — Creating a New User

Once you are logged in as root, you’ll be able to add the new user account. In the future, we’ll log in with this new account instead of root.

This example creates a new user called asit, but you should replace that with a username that you like:

```bash
  adduser asit
```

You will be asked a few questions, starting with the account password.

Enter a strong password and, optionally, fill in any of the additional information if you would like. This is not required and you can just hit ENTER in any field you wish to skip.

### Step 3 — Granting Administrative Privileges

Now we have a new user account with regular account privileges. However, we may sometimes need to do administrative tasks.

To avoid having to log out of our normal user and log back in as the root account, we can set up what is known as superuser or root privileges for our normal account. This will allow our normal user to run commands with administrative privileges by putting the word sudo before the command.

To add these privileges to our new user, we need to add the user to the sudo group. By default, on Ubuntu 22.04, users who are members of the sudo group are allowed to use the sudo command.

As root, run this command to add your new user to the sudo group (substitute the highlighted username with your new user):

```bash
usermod -aG sudo
```

Now, when logged in as your regular user, you can type sudo before commands to run them with superuser privileges.

### Step 4 — Setting Up a Basic Firewall

Ubuntu 22.04 servers can use the UFW firewall to make sure only connections to certain services are allowed. We can set up a basic firewall using this application.

Applications can register their profiles with UFW upon installation. These profiles allow UFW to manage these applications by name. OpenSSH, the service allowing us to connect to our server now, has a profile registered with UFW.

You can see this by typing:

```bash
ufw app list
```
**Output:**
```
Available applications:
  OpenSSH
```
We need to make sure that the firewall allows SSH connections so that we can log back in next time. We can allow these connections by typing:

```bash
ufw allow OpenSSH
```

Afterwards, we can enable the firewall by typing:

```bash
ufw enable
```
Type `y` and press `ENTER` to proceed. You can see that SSH connections are still allowed by typing:

```bash
ufw status
```

**Output:**
```bash
Status: active

To                         Action      From
--                         ------      ----
OpenSSH                    ALLOW       Anywhere
OpenSSH (v6)               ALLOW       Anywhere (v6)
```
As the firewall is currently blocking all connections except for SSH, if you install and configure additional services, you will need to adjust the firewall settings to allow traffic in. 

## Install and Use Docker on Ubuntu 22.04

To setup Docker, you need to follow the steps below:

### Step 1 — Installing Docker

The Docker installation package available in the official Ubuntu repository may not be the latest version. To ensure we get the latest version, we’ll install Docker from the official Docker repository. To do that, we’ll add a new package source, add the GPG key from Docker to ensure the downloads are valid, and then install the package.

First, update your existing list of packages:

```bash
sudo apt update
```

Next, install a few prerequisite packages which let `apt` use packages over HTTPS:

```bash
sudo apt install apt-transport-https ca-certificates curl software-properties-common
```

Then add the GPG key for the official Docker repository to your system:

```bash
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
```
Add the Docker repository to APT sources:
```bash
sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu focal stable"
```

This will also update our package database with the Docker packages from the newly added repo.

Make sure you are about to install from the Docker repo instead of the default Ubuntu repo:

```bash
apt-cache policy docker-ce
```

You’ll see output like this, although the version number for Docker may be different:

```bash
docker-ce:
  Installed: (none)
  Candidate: 5:19.03.9~3-0~ubuntu-focal
  Version table:
     5:19.03.9~3-0~ubuntu-focal 500
        500 https://download.docker.com/linux/ubuntu focal/stable amd64 Packages
```

Notice that `docker-ce` is not installed, but the candidate for installation is from the Docker repository for Ubuntu 22.04.

Finally, install Docker:

```bash
sudo apt install docker-ce
```

Docker should now be installed, the daemon started, and the process enabled to start on boot. Check that it’s running:

```bash
sudo systemctl status docker
```

The output should be similar to the following, showing that the service is active and running:

**Output:**
```bash
● docker.service - Docker Application Container Engine
     Loaded: loaded (/lib/systemd/system/docker.service; enabled; vendor preset: enabled)
     Active: active (running) since Tue 2020-05-19 17:00:41 UTC; 17s ago
TriggeredBy: ● docker.socket
       Docs: https://docs.docker.com
   Main PID: 24321 (dockerd)
      Tasks: 8
     Memory: 46.4M
     CGroup: /system.slice/docker.service
             └─24321 /usr/bin/dockerd -H fd:// --containerd=/run/containerd/containerd.sock
```
Installing Docker now gives you not just the Docker service (daemon) but also the `docker` command line utility, or the Docker client. We’ll explore how to use the `docker` command later in this tutorial.

## Install and Use Docker Compose on Ubuntu 22.04

Now that we have set up `docker`, lets set up `docker-compose`

### Step 1 — Installing Docker Compose

To make sure you obtain the most updated stable version of Docker Compose, you’ll download this software from its official [Github repository](https://github.com/docker/compose). 

First, confirm the latest version available in their [releases page](https://github.com/docker/compose/releases). At the time of this writing, the most current stable version is `v2.20.3`.

The following command will download the `2.20.3` release and save the executable file at /`usr/local/bin/docker-compose`, which will make this software globally accessible as `docker-compose`:

```bash
sudo curl -L "https://github.com/docker/compose/releases/download/2.20.3/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
```

Next, set the correct permissions so that the `docker-compose` command is executable:

```bash
sudo chmod +x /usr/local/bin/docker-compose
```

To verify that the installation was successful, you can run:

```bash
docker-compose --version
```

You’ll see output similar to this:

**Output:**
```bash
docker-compose version 2.20.3, build 5becea4c
```

Docker Compose is now successfully installed on your system. In the next section, you’ll see how to set up a docker-compose.yml file and get a containerized environment up and running with this tool.🎉

## Set up CloudFlare

To get the security, performance, and reliability benefits of Cloudflare, you need to set up Cloudflare on your domain:

We will be using CloudFlare for its free Content Delivery Network (CDN), Web Application Firewall (WAF), Distributed Denial-of-Service (DDoS) Attack Protection

- Create your account: Create a new account with Cloudflare and adjust account settings as needed.

- Minimize downtime(for some): 
  - Update and review DNS records
    
    Before activating your domain on Cloudflare (exact steps depend on your DNS setup), review the DNS records in your Cloudflare account.

  - Start with unproxied records
  - Now add `A` records unproxied to your server's IP address

- You can check DNS propagation status on [DNS CHECKER](https://dnschecker.org/)
- WAF protection we will do later once everything is set up.

Once you have environment set up, you’re ready to begin the first step towards setting up the project

## Other Prerequisites

- A registered domain name. You can get one for free at Freenom, or use the domain registrar of your choice.

- Your domain's NameServer(NS) records needs to be pointed to CloudFlare

## Local Development Environment Setup

Follow these steps to set up the local development environment using Docker Compose:

### Step 1: Clone this repository:

First fork the repo and then clone the repo

```bash
git clone https://github.com/your-username/wordpress-docker-github
```

### Step 1: Navigate to the project directory:

```bash
cd wordpress-docker-github
```

### Step 3: Defining Environment Variables

Your database and WordPress application containers will need access to certain environment variables at runtime in order for your application data to persist and be accessible to your application. These variables include both sensitive and non-sensitive information: sensitive values for your MySQL root password and application database user and password, and non-sensitive information for your application database name and host.

In your main project directory, ~/wordpress-docker-github, open a file called .env:

```bash
nano .env
```

The confidential values that you set in this file include a password for the MySQL root user, and a username and password that WordPress will use to access the database.

Add the following variable names and values to the file. Remember to supply your own values here for each variable:

```bash
MYSQL_ROOT_PASSWORD = your_root_password
MYSQL_USER = your_wordpress_database_user
MYSQL_PASSWORD = your_wordpress_database_password
```

Included is a password for the root administrative account, as well as your preferred username and password for your application database.

Save and close the file when you are finished editing.

Because your `.env` file contains sensitive information, you want to ensure that it is included in your project’s `.gitignore` and `.dockerignore` files. This tells Git and Docker what files not to copy to your Git repositories and Docker images, respectively.

If you plan to work with Git for version control, initialize your current working directory as a repository with `git init`:

```bash
git init
```

Then create and open a `.gitignore` file:

```bash
nano .gitignore
```

Add `.env` to the file:

```bash
.env
```

Save and close the file when you are finished editing.

Likewise, it’s a good precaution to add `.env` to a `.dockerignore` file, so that it doesn’t end up on your containers when you are using this directory as your build context.

Open the file:

```console
nano .dockerignore
```

Add .env to the file:

```bash
.env
```

Below this, you can optionally add files and directories associated with your application’s development:

```bash
.env
.git
docker-compose.yml
.dockerignore
```

Save and close the file when you are finished.

With your sensitive information in place, you can now move on to defining your services in a `docker-compose.yml` file.

### Step 4: Docker Compose

Now that you have docker-compose.yml, .env, etc set up, we will start docker-compose.

We have 4 Images in the Docker-compose,
- database: MySQL
- wordpress: Wordpress CMS
- nginx: nginx server
- certbot: used for obtaining SSL certificates that will be used by nginx, once certbot container gets the SSL certificate, the container will stop working


### Step 5: Obtaining SSL Certificates and Credentials

Before we start, you need to `comment` out following lines from `nginx-conf/nginx.conf`

#### Nginx Config

```console
listen 443 ssl http2;
listen [::]:443 ssl http2;

ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
```

and replace the `domain` in the `nginx.conf` with your `domain`

#### docker-compose setup

Open editor for `docker compose`

```bash
nano docker-compose.yml
```
In the certbot block, find the `domain` name and replace with your `domain` and `username` at `user` and change `--force-renewal` to `--staging` for now. We will change it back to `--force-renewal` once we are all done with the setup.

```bash
command: certonly --webroot --webroot-path=/var/www/html --email user@yourdomain.com --agree-tos --no-eff-email --staging -d yourdomain.com
```

Start your containers with the `docker-compose up` command, which will create and run your containers in the order you have specified. By adding the `-d` flag, the command will run the `db`, `wordpress`, and `webserver` containers in the background:

```bash
docker-compose up -d
```

The following output confirms that your services have been created:

**Output:**
```console
Creating db ... done
Creating wordpress ... done
Creating webserver ... done
Creating certbot   ... done
```

Using `docker-compose` ps, check the status of your services:

```bash
docker-compose ps
```

Once complete, your `db`, `wordpress`, and `webserver` services will be `Up` and the `certbot` container will have exited with a 0 status message:

**Output:**
```console
  Name                 Command               State           Ports       
-------------------------------------------------------------------------
certbot     certbot certonly --webroot ...   Exit 0                      
db          docker-entrypoint.sh --def ...   Up       3306/tcp, 33060/tcp
webserver   nginx -g daemon off;             Up       0.0.0.0:80->80/tcp 
wordpress   docker-entrypoint.sh php-fpm     Up       9000/tcp         
```

Anything other than `Up` in the `State` column for the `db`, `wordpress`, or `webserver` services, or an exit status other than `0` for the `certbot` container means that you may need to check the service logs with the `docker-compose logs` command:

```bash
docker-compose logs service_name
```

You can now check that your certificates have been mounted to the `webserver` container with `docker-compose exec`:

```bash
docker-compose exec webserver ls -la /etc/letsencrypt/live
```

Once your certificate requests succeed, the following is the output:

**Output:**
```console
total 16
drwx------    3 root     root          4096 May 10 15:45 .
drwxr-xr-x    9 root     root          4096 May 10 15:45 ..
-rw-r--r--    1 root     root           740 May 10 15:45 README
drwxr-xr-x    2 root     root          4096 May 10 15:45 your_domain
```

### Step 6: Completing the Installation Through the Web Interface

With your containers running, finish the installation through the WordPress web interface.

In your web browser, navigate to your server’s domain. Remember to substitute `your_domain` with your own domain name:

```console
https://your_domain
```

### Step 7: Set Up Wordpress

#### First remove all the default files

- Remove default plugins
- Remove Hello world post

#### Install and Activate security plugins

- Wordfence Security
- WP fail2ban
- WP fail2ban Blocklist
- WPS Hide Login

Once this setup is done, check if the `docker-compose` and `website` is running

You can check if the docker-compose is working by using following command in the directory where you have cloned the repo.

```bash
docker-compose ps
```

### Step 8: Setting up Github Actions

- Log into your [Github account](https://github.com/)
- Navigate to your `repo`

- Now go to settings > secrets and variables > actions
- Now create `New Repository Secret`

These `secrets` will be used to create the `variables` that we created in `.env` to use with `github actions`

Once you have added the `Credentials` make sure you have the variables names noted and changed in your repo `.github/workflows/Deploy_WordPress.yml`

Now go to `Actions` on github dashboard and set up / check if `Deploy_WordPress.yml` has been connected.

Now we need to set up `runners` for `github actions` to work on our server.

A self-hosted runner is a system that you deploy and manage to execute jobs from GitHub Actions on GitHub.com.

- Go to your repo > settings > actions > runners
- Click on `New Self-Hosted Runner`
- Now select the appropriate `OS` and `system architecture`
- Just `copy paste` the commands in your system to set up the `runner` and continue with default settings

Now try running your github actions job.

### Step 9: Set Up SSL

- Now we need to change `--staging` to `--force-renewal` in `docker-compose.yml`
- Go to your github repo, and edit `nginx-conf/nginx.conf`
- In the certbot block, change `--staging` to `--force-renewal`.



Now we will `edit nginx.conf`
You need to `remove the comments` from the following lines from `nginx-conf/nginx.conf`

```console
listen 443 ssl http2;
listen [::]:443 ssl http2;

ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
```

Now run the github actions once again.

### Cloudflare Protection

- Go to `cloudflare dashboard` and navigate to `DNS`
- Change the `proxy status` of your `A record` from `DNS only` to `Proxied`
- Now navigate to Security > WAF > Custom Rules

**To add rules follow these steps:**
- Add rule name
- Click on `Edit expression` and the following expressions
- Choose action: `Block`
- Deploy

#### **Rule 1**
- **Rule name:** Block wp-login.php Attacks
- **Expression Preview:** (http.request.uri.path contains "/wp-login.php")
- **Action:** Block

#### **Rule 2**
- **Rule name:** Block xmlrpc.php Attacks
- **Expression Preview:** (http.request.uri.path contains "/xmlrpc.php")
- **Action:** Block

#### **Rule 3**
- **Rule name:** Block No-Referer Requests to Plugins
- **Expression Preview:** (http.request.uri.path contains "/wp-content/plugins/" and not http.referer contains "yoursite.com" and not cf.client.bot)
- **Action:** Block

#### **Rule 4**
- **Rule name:** Reduce Spam by Blocking Direct Requests to wp-comments-post.php
- **Expression Preview:** (http.request.uri.path eq "/wp-comments-post.php" and http.request.method eq "POST" and not http.referer contains "yoursite.com")
- **Action:** Block

Thanks All! Enjoy your wordpress website!
