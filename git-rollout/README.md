git-rollout
===========

This directory contains git-rollout, a small container and kubernetes config to update
rollout new containers whenever some git repository is updated.

At the MI, this is used to make the goemaxima containers automatically restart with new
macro files downloaded from git (see also the enableGitRollout and gitRollout values
in helmmaxima).

It has to be in the same namespace as the goemaxima instances and looks for the label
gitrollout=goemaxima label, which is already added if you use the helmmaxima chart with
enableGitRollout value set to true.
