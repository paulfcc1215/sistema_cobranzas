<?php
class Helpers_Filesystem {
    static function getTree($path,$maxdepth=-1,$only_dirs=false) {
        $iterator=new RecursiveDirectoryIterator($path);
        
            $itit=new RecursiveIteratorIterator($iterator,RecursiveIteratorIterator::SELF_FIRST);
            $itit->setMaxDepth($maxdepth);
        
        foreach($itit as $f) {
            $filename=$f->getFilename();
            $isDir=$f->isDir();
            if($filename=='.' || $filename=='..') continue;
            if($only_dirs && !$isDir) continue;
            
            $file=array();
            $file['ATime']=$f->getATime();
            $file['Basename']=$f->getBasename();
            $file['CTime']=$f->getCTime();
            //$file['Extension']=$f->getExtension();
            //$file['FileInfo']=$f->getFileInfo();
            $file['Filename']=$filename;
            $file['Group']=$f->getGroup();
            $file['Inode']=$f->getInode();
            //$file['LinkTarget']=$f->getLinkTarget();
            $file['MTime']=$f->getMTime();
            $file['Owner']=$f->getOwner();
            $file['Path']=$f->getPath();
            //$file['PathInfo']=$f->getPathInfo();
            $file['Pathname']=$f->getPathname();
            $file['Perms']=$f->getPerms();
            $file['RealPath']=$f->getRealPath();
            $file['Size']=$f->getSize();
            $file['Type']=$f->getType();
            $file['isDir']=$isDir;
            $file['isExecutable']=$f->isExecutable();
            $file['isFile']=$f->isFile();
            $file['isLink']=$f->isLink();
            $file['isReadable']=$f->isReadable();
            $file['isWritable']=$f->isWritable();
            $files[]=$file;
        }
        return $files;
    }
    
    
    
}