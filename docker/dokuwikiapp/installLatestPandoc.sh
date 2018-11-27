#!/bin/bash
temp_dir=$(mktemp -d)
curl -s https://api.github.com/repos/jgm/pandoc/releases/latest | grep browser_download_url | grep amd64.deb | cut -d '"' -f 4 | wget 
dpkg -i $temp_dir/*amd64.deb
rm -rf $temp_dir