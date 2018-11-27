FROM mprasil/dokuwiki

ARG MAXPOST=8M
ARG MAXFILE=2M

RUN apt-get update && \
#  apt-get upgrade -y && \
apt-get install -y nano sudo wget curl default-jre libreoffice-writer

COPY installLatestPandoc.sh /installLatestPandoc.sh
RUN chmod +x /installLatestPandoc.sh && /installLatestPandoc.sh

RUN echo "www-data ALL=(root)NOPASSWD:/usr/bin/soffice" >> /etc/sudoers.d/soffice && \
  chmod 440 /etc/sudoers.d/soffice

RUN sed -i.bak 's/\(^ *post_max_size\ *= *\).*/\1'$MAXPOST'/' /etc/php/7.0/cgi/php.ini && \
  sed -i 's/\(^ *upload_max_filesize\ *= *\).*/\1'$MAXFILE'/' /etc/php/7.0/cgi/php.ini
