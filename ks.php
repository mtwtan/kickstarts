<?php
function gheaders($hname) {
  $hval = "Nothing";
  foreach ( getallheaders() as $name => $value ) {
     if ( $name == $hname ) {
        $hval = $value;
     }
  }
  return $hval;
}
function filetoarray($filename) {
  $farray = array();
  $file = fopen($filename, "r");
  if (is_readable($filename)) {
    $file = fopen($filename, "r");
  } else {
    die("The file " . $filename . " is not readable");
  }
  while ( ! feof($file) ) {
    array_push($farray,fgetcsv($file));
  }
  fclose($file);
  
  return $farray;
}
function getServerInfo($mac_address) {
  $filename = "/var/local/kickstart/server_list.csv";
  $farray = (filetoarray($filename));
  $hostname = "No Hostname";
  
  foreach($farray as $array_value) {
    if ( $array_value[0] == $mac_address ) {
       $hostname = $array_value[1];
    }
  }
  return $hostname;
}
function writeLog($datetime,$content_tolog) {
  
  $filename = "/var/local/kickstart/logs/ksinstall.log";
  $content = $datetime . ": " . $content_tolog . "\n";
  file_put_contents($filename, $content, FILE_APPEND);
}
// Set variables
date_default_timezone_set('UTC');
$datetime = date('l jS \of F Y h:i:s A');
$header_mac_address = gheaders("X-RHN-Provisioning-MAC-0");
  
preg_match('/(?<eth>[a-zA-Z0-9]+)\s(?<mac>[a-zA-Z0-9:]+)/', $header_mac_address, $matches);
$eth_device = $matches["eth"];
$mac_address = $matches["mac"];
$hostname = getServerInfo($mac_address);
  
$content = "Device " . $eth_device . " connected with MAC address: " . $mac_address;
writeLog($datetime,$content);
$content = "Hostname assigned to device " . $eth_device . " with MAC address " . $mac_address . " is " . $hostname;
writeLog($datetime,$content);
  
/* **********************************
 * For testing only
 *
echo "MAC: $mac_address\n\n";
echo "Hostname: $hostname\n\n";
 *
 * **********************************
*/
?>
#platform=x86, AMD64, or Intel EM64T
#version=DEVEL
# Install OS instead of upgrade
install
# Keyboard layouts
keyboard 'us'
# Root password
rootpw --iscrypted << Enter encrypted password here >>
#
# Create user sysadmin
## Password created by: echo 'import crypt,getpass; print crypt.crypt(getpass.getpass(), "$1$8_CHARACTER_SALT_HERE")' | python -
user --name=sysadmin --groups=wheel --password=<< Enter encrypted password here >> --iscrypted
#
#
# Use network installation
url --url="http://<domain name>/<location>"
# System language
lang en_US
# Firewall configuration
firewall --enabled --ssh
#
# System authorization information
auth  --useshadow  --passalgo=sha512
##### Uncomment to use graphical install
#graphical
#
##### Uncomment to use text install
text
#
firstboot --disable
# SELinux configuration
selinux --enabled
# Network information
### Change the device name as necessary
network  --bootproto=dhcp --device=enp0s31f6 --hostname=<?php echo $hostname; ?>

# System timezone
timezone Etc/UTC --isUtc
# System bootloader configuration
bootloader --location=mbr
# Clear the Master Boot Record
zerombr
# Partition clearing information
clearpart --all
# Disk partitioning information
### Change partitioning as necessary
###
part /boot --fstype="xfs" --ondisk=sda --size=500
part /boot/efi --fstype="xfs" --ondisk=sda --size=500
part /swap --fstype="xfs" --ondisk=sda --size=8000
part / --fstype="xfs" --grow --ondisk=sda --size=1
part /home --fstype="xfs" --grow --ondisk=sdb --size=1

#Install yum repo
repo --name=server --baseurl=http://<repo domain name>/<path>/ --install
repo --name=extras --baseurl=http://<repo domain name>/<path>/ --install
repo --name=common --baseurl=http://<repo domain name>/<path>/ --install

%packages
@^GNOME Desktop
@Graphical Administration Tools
@Development Tools
############################
#  Additional Packages   ##
############################
chrony
%end


#Post installation script
%post

#systemctl disable rhsmcertd.service
#subscription-manager config --rhsm.manage_repos=0
#sed -i 's/enabled=1/enabled=0/' /etc/yum/pluginconf.d/subscription-manager.conf

systemctl set-default graphical.target

%end

# Reboot system
reboot