FROM httpd:2.4

ARG USER_ID
ARG GROUP_ID

RUN groupadd -g $GROUP_ID app && \
    useradd -r -u $USER_ID -g $GROUP_ID app

RUN chown -R app:app /usr/local/apache2/logs

USER app