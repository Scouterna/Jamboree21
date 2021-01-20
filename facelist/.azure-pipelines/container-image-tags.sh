#! /bin/bash
find $K8SDIR/* -print0 | xargs -0 sed -i "s@%DOWNLOADER_IMAGE%@scouterna.azurecr.io/$SERVICE-dl:$BRANCH-$VERSION@"
find $K8SDIR/* -print0 | xargs -0 sed -i "s@%IMAGE%@scouterna.azurecr.io/$SERVICE:$BRANCH-$VERSION@"
