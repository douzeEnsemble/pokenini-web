FROM alpine:latest

ENV MOCO_ROOT=1

RUN apk add --no-cache \
		openjdk11-jre \
		curl \
		bash \
	&& wget https://repo1.maven.org/maven2/com/github/dreamhead/moco-runner/1.3.0/moco-runner-1.3.0-standalone.jar -O /usr/local/bin/moco.jar \
	&& mkdir -p /var/moco \
	&& printf "[]" > /var/moco/moco.json \
	&& chmod 777 /var/moco/moco.json \
	&& rm -rf \
		/tmp/* \
		/var/cache/apk/*

ENTRYPOINT ["java", "-jar", "/usr/local/bin/moco.jar"]

EXPOSE 8000
CMD ["start", "-p", "80", "-c", "/var/moco/test.moco.json"]
