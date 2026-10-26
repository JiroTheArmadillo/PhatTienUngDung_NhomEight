<?php 

    class ChuongTrinhKhuyenMai{

        private  string $tenKhuyenMai;
        private  string $maKhuyenMai;
        private int $luotDung;
                
        public  function  __construct(string $maKhuyenMai,string $tenKhuyenMai,int $luotDung){

            $this->maKhuyenMai = $maKhuyenMai;
            $this->tenKhuyenMai = $tenKhuyenMai;
            $this->luotDung = $luotDung;    

        }

        public function getTenKhuyenMai(): string{
            return $this->tenKhuyenMai;
        }

        
        public function getMaKhuyenMai(): string{
            return $this->maKhuyenMai;
        }
        
        public function getLuotDung(): int{
            return $this->luotDung;
        }


        public function setTenKhuyenMai(string $tenKhuyenMai): void{
            $this->tenKhuyenMai = $tenKhuyenMai;
        }

        
        public function setMaKhuyenMai(string $maKhuyenMai): void{
            $this->maKhuyenMai = $maKhuyenMai;
        }
        
        public function setLuotDung(int $luotDung): void{
            $this->luotDung = $luotDung;
        } 

    }



?>