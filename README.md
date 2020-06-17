goemaxima
=========
This project developed at the Mathematical Institute of Goettingen provides an alternative webservice for stack, which is a question type for ilias and moodle focused on math.
It is mainly intended to be used as a docker container in a cluster, optimally one supporting autoscaling, as scaling in the service itself is not supported.

This implementation in golang strives to have faster startup and processing times than the corresponding java version by using fork instead of starting a new process for each request.
For some more information on how this works, [see the documentation](/doc/How_it_works.md).
