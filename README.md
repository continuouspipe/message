# ContinuousPipe Message

This library contains the basic classes used to be able to publish and consume messages in or between ContinuousPipe
services.

## Symfony Bundle usage

```yaml
message:
    driver: google-pub-sub
```

With asychronous simple bus:
```yaml
worker:
    service: simple_bus.rabbit_mq_bundle_bridge.commands_consumer

simple_bus_asynchronous:
    commands:
        logging: ~
        publisher_service_id: continuouspipe.message.simple_bus.producer
```
