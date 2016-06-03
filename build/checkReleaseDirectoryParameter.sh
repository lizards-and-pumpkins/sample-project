if [[ "$1" != /* ]]; then
  echo "Absolute Release directory path must be specified as script argument (e.g. init.sh /path/)."
  exit 1
fi

if [ ! -d "$1" ]; then
  echo "Release directory $1 does not exist."
  exit 1
fi
