Helm Chart for `goemaxima`
==========================

This is a helm chart for deploying goemaxima to kubernetes.
It includes an autoscaler that automatically adds goemaxima instances on high load.

An example value file is provided in `example.yaml`.
After setting the version and url in `example.yaml`, it can be deployed with [helm](https://helm.sh/) by running

```
helm install -f example.yaml goemaxima-example .
```

in this directory.

For more configuration options, see also the values.yaml file.
