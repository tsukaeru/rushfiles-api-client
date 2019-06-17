## Client construction

If no `deviceId` is provided, it will be automatically generated* from `deviceName` (default to `Tsukaeru\RushFiles`). To differentiate your application, please provider a unique device name, or even your own `deviceId`.

\* A uuid ver 5 will be generated with `8737930c-8f13-11e9-910b-7e7a262c9c6d` as namespace and `deviceName` as name.