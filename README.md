# dokuwiki-plugin-file2dw

NOT USABLE YET.

Import from file (docx, odt, pdf...) to dokuwiki.

- More information about file2dw at http://www.dokuwiki.org/plugin:file2dw
- More information about dokuwiki at http://www.dokuwiki.org
- More information about pandoc at http://pandoc.org
- More information about LibreOffice at https://www.libreoffice.org/

## Description

file2dw is a plugin for dokuwiki. This plugin lets you import a document into Dokuwiki. It supports (at least) .odt, .doc, .docx formats. It should also work with any other document format that pandoc supports (are a lot).

## Usage

From a Dokuwiki page, click on the "Import file" button in the Page Tools. Select a File and click upload.

## Installation 

**External requirements:** This plugin requires the following additional components that must be installed separately:

- pandoc
- soffice (ex: libreoffice-writer)

If you run the Dokuwiki server on Debian, you can accomplish this requirements following this directions:

- Install some packages needed:

`sudo apt-get wget default-jre libreoffice-writer`

- If you wish, you can execute the script `installLatestPandoc.sh` (included with this plugin) to install the latest version of pandoc. Or you can install pandoc any other way (check that version installed is not very outdated, or conversion may fail).

### soffice conversion (.doc support)

PHP code (at least in my system) is executed by the user `daemon`. I had problems running a Java application with this user (it seems soffice is a Java application) so I decided to run the soffice conversion using `sudo`. To make it work, I had to add a line to the file `/etc/sudoers`. You can do the same executing: 

`sudo echo "daemon ALL=(root)NOPASSWD:/usr/bin/soffice" >> /etc/sudoers`

I'm not a security expert, but I think that this should not be a problem for anybody. If you do not use the soffice conversion (.doc support), you don't need to do this.

If PHP code is executed by any other user on your system, you only have to change it in the previous command.

## Configuration and Settings

[TO-DO].

## Change Log

### v0.1 beta

- Initial version.
