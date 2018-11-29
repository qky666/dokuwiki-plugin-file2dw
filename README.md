# dokuwiki-plugin-file2dw

Create a dokuwiki page by importing a file.

- More information about file2dw at http://www.dokuwiki.org/plugin:file2dw
- More information about dokuwiki at http://www.dokuwiki.org
- More information about pandoc at http://pandoc.org
- More information about LibreOffice at https://www.libreoffice.org/

## Description

file2dw is a plugin for dokuwiki. This plugin lets you import a document into Dokuwiki. It supports (at least) .odt, .doc, .docx formats. It should also work with any other document format that pandoc supports (they are a lot).

## Usage

From a Dokuwiki page, click on the "Import file" button in the Page Tools. Select a File and click upload.

## Installation

### *Direct* install

**External requirements:** This plugin requires the following additional components that must be installed separately:

- pandoc
- soffice (example: libreoffice-writer)

If you run the Dokuwiki server on Debian, you can accomplish this requirements following this directions:

- Install some packages needed:

`sudo apt-get wget default-jre libreoffice-writer`

- If you wish, you can execute the script `installLatestPandoc.sh` (included with this plugin in `docker/dokuwikiapp` folder) to install the latest version of pandoc. Or you can install pandoc any other way (check that version installed is not very outdated, or conversion may fail).

#### soffice conversion (.doc support)

PHP code (at least in my system) is executed by the user `www-data`. I had problems running a Java application with this user (it seems soffice is a Java application) so I decided to run the soffice conversion using `sudo`. To make it work, I had to add a line to the file `/etc/sudoers`. You can do the same executing: 

`sudo echo "www-data ALL=(root)NOPASSWD:/usr/bin/soffice" >> /etc/sudoers`

I'm not a security expert, but I think that this should not be a problem for anybody. If you do not use the soffice conversion (.doc support), you don't need to do this.

If PHP code is executed by any other user on your system, you only have to change it in the previous command.

### Docker

If you want, you can use Docker to deploy Dokuwiki and meet the requirements mentioned above "for free". If you are new to Docker, you will probably need to search the web for information before you can use it. See https://www.docker.com/

You will have to make several changes in `docker-compose-yml` files (at least in one of them). I hope you will find usefull the comments on the file. To prevent errors, you can copy the `docker` folder contents elsewhere in your system, so you don't lose these changes when you get updates. `docker-compose-yml` files use the files in `docker/dokuwikiapp`, so take care when moving this arround. 

**Note**: This creates an "empty" Dokuwiki, so you will have to follow the install instructions in https://www.dokuwiki.org/install (from step 4) to make it usable.

Two "flavours" are provided: 

- `simple`
- `proxy`

#### Simple

Path: `docker/simple`. 

A simple `docker-compose.yml` that will create a container with Dokuwiki installed on it. 

To run this, execute:

```
cd docker/simple
docker-compose up -d
```

#### Proxy

Path: `docker/proxy`. 

A `docker-compose.yml` that will create a container with Dokuwiki installed on it and a reverse proxy that allows `https` access to it.

It uses Let's Encrypt services (https://letsencrypt.org/) so you will have to do a little reserach to know how it works. 

To run this, execute:

```
cd docker/proxy
docker-compose up -d
```

## Usage

From a Dokuwiki page, click on the "Import file" button in the Page Tools. Select a File and click upload.

## Configuration and Settings

They are almost self-explanatory.

## Change Log

### v0.1 beta

- Initial version.
