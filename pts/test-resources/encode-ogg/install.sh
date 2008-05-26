#!/bin/sh

if [ ! -f ../pts-shared/pts-trondheim-2.wav ]
  then
     tar -xvf ../pts-shared/pts-trondheim-wav-2.tar.gz -C ../pts-shared/
fi

THIS_DIR=$(pwd)
mkdir $THIS_DIR/vorbis

tar -xvf libogg-1.1.3.tar.gz
tar -xvf libvorbis-1.2.0.tar.gz
tar -xvf vorbis-tools-1.2.0.tar.gz

cd libogg-1.1.3/
./configure --prefix=$THIS_DIR/vorbis
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf libogg-1.1.3/

cd libvorbis-1.2.0/
./configure --prefix=$THIS_DIR/vorbis
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf libvorbis-1.2.0/

cd vorbis-tools-1.2.0/
./configure --prefix=$THIS_DIR/vorbis
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf vorbis-tools-1.2.0/

echo "#!/bin/sh
/usr/bin/time -f \"WAV To OGG Encode Time: %e Seconds\" ./vorbis/bin/oggenc --quiet ../pts-shared/pts-trondheim.wav -q 10 -o /dev/null 2>&1" > oggenc
chmod +x oggenc
