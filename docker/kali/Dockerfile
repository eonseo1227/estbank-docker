FROM kalilinux/kali-rolling
RUN echo "deb http://kali.download/kali kali-rolling main contrib non-free non-free-firmware" > /etc/apt/sources.list \
 && apt-get update \
 && apt-get install -y --no-install-recommends nmap hping3 iproute2 iputils-ping curl \
 && rm -rf /var/lib/apt/lists/*
CMD ["sleep", "infinity"]
