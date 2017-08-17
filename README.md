# ContinuousPipe Message

This library contains the basic classes used to be able to publish and consume messages in or between ContinuousPipe
services.

## Symfony Bundle usage

```yaml
message:
    simple_bus:
        connection: default
    command:
        connection: default
        message_dealine_expiration_manager: continuouspipe.message.default.message_puller
    connections:
        default:
            driver:
                google_pub_sub:
                    project_id: %google_pub_sub_project_id%
                    service_account_path: %google_pub_sub_key_file_path%
                    topic: %google_pub_sub_topic%
                    subscription: %google_pub_sub_subscription_name%
            debug: false
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
