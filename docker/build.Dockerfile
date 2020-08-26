FROM alpine:3

RUN apk add --no-cache zip dos2unix composer

ENTRYPOINT ["sleep", "infinity"]