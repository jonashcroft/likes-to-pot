# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|

    config.vm.box = "scotch/box"

  # config.vm.box_version = 2.5

    config.vm.network "private_network", ip: "192.168.33.10"
    config.vm.hostname = "likestopot.test"
    # config.vm.synced_folder ".", "/var/www", :mount_options => ["dmode=777", "fmode=666"]

    # Optional NFS. Make sure to remove other synced_folder line too
    config.vm.synced_folder ".", "/var/www", :nfs => { :mount_options => ["dmode=777","fmode=666"] }

    # Double virtual memory and virtual cpu
    config.vm.provider "virtualbox" do |v|
      v.name = "likestopot"
      v.memory = 1536
      v.cpus = 2
    end

    # Optional - remove domain name from /etc/hosts on close
    config.hostsupdater.remove_on_suspend = false

end
