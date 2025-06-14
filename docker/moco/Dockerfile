FROM alpine:3.22

ARG MOCO_VERSION

ENV MOCO_ROOT=1

RUN apk add --no-cache \
		openjdk11-jre=~11.0 \
		curl=~8.14 \
		bash=~5.2 \
	&& wget -q -nv \ 
		"https://repo1.maven.org/maven2/com/github/dreamhead/moco-runner/$MOCO_VERSION/moco-runner-$MOCO_VERSION-standalone.jar" \
		-O /usr/local/bin/moco.jar \
	&& mkdir -p /var/moco \
	&& printf "[]" > /var/moco/moco.json \
	&& chmod 777 /var/moco/moco.json \
	&& rm -rf tmp/* /var/cache/apk/*

ENTRYPOINT ["java", "-jar", "/usr/local/bin/moco.jar", "start", "-p", "80", "-c"]
CMD ["/var/moco/moco.json"]
