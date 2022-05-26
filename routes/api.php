<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthMasterMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get("/", function(Request $requset){
    return "YAKOPI API LARAVEL v1.0";
});

// INFORMATION API START

Route::get("/mobilebuildnumber", function(Request $request){
    $version = DB::select("SELECT buildnumber,changelog_mobile FROM yakopi_identitas WHERE id_profile=1");
    return [
        "buildnumber"=>$version[0]->buildnumber,
        "changelog_mobile"=>$version[0]->changelog_mobile
    ];
});

Route::post("/auth", function (Request $request){

    $username = $request->email;
    $password = $request->password;
    
    $account = DB::select("SELECT * FROM yakopi_pengguna WHERE yakopi_pengguna.username=? OR yakopi_pengguna.email=?",[$username,$username]);

    if(count($account)>0){
        $u = $account[0]->username;
        $p = $account[0]->password;
        $e = $account[0]->email;
        
        $sha1p = sha1($password);

        if($u==$username && $sha1p==$p || $e==$username && $sha1p==$p){

            $update = DB::update("UPDATE yakopi_pengguna SET last_login=NOW() WHERE username=?",[$username]);
            $modul = DB::select("SELECT * FROM yakopi_hak_akses WHERE nama_hak_akses=?",[$account[0]->hak_akses]);

            return [
                "success"=>true,
                "data"=>$account[0],
                "modul"=>$modul[0],
                "token"=>Crypt::encryptString(json_encode($account[0]))
            ];
        }
        else{
            return [
                "success"=>false,
                "msg"=>"Login gagal"
            ];
        }
        
    
    }
    else{
        return [
            "success"=>false,
            "msg"=>"Tidak ditemukan akun tersebut"
        ];
    }

});


Route::middleware([AuthMasterMiddleware::class])->group(function () {

    //GENERAL API START

    // LOCATION API START

    Route::get("/city", function (Request $request){
        $city = DB::select("SELECT * FROM yakopi_cities");
        return $city;
    });

    Route::get("/city/{id}", function (Request $request, $id){
        $city = DB::select("SELECT * FROM yakopi_cities WHERE prov_id=?",[$id]);
        return [
            "success"=>true,
            "data"=>$city
        ];
    });

    Route::get("/district", function (Request $request){
        $district = DB::select("SELECT * FROM yakopi_districts");
        return [
            "success"=>true,
            "data"=>$district
        ];
    });

    Route::get("/district/{id}", function (Request $request, $id){
        $district = DB::select("SELECT * FROM yakopi_districts WHERE city_id=?",[$id]);
        return [
            "success"=>true,
            "data"=>$district
        ];
    });

    Route::get("/province", function (Request $request){
        $province = DB::select("SELECT * FROM yakopi_provinces");
        return [
            "success"=>true,
            "data"=>$province
        ];
    });

    Route::get("/province/{id}", function (Request $request, $id){
        $province = DB::select("SELECT * FROM yakopi_provinces WHERE prov_id=?",[$id]);
        return [
            "success"=>true,
            "data"=>$province
        ];
    });

    // LOCATION API END

    // PHOTO API START

    Route::get("/photo_restoration", function (Request $request){
        $photo = DB::select("SELECT * FROM yakopi_photo_restoration LIMIT 10");
        return [
            "success"=>true,
            "data"=>$photo
        ];
    });

    Route::get("/photo_comdev", function (Request $request){
        $photo = DB::select("SELECT * FROM yakopi_photo_comdev LIMIT 10");
        return [
            "success"=>true,
            "data"=>$photo
        ];
    });

    Route::get("/photo_research", function (Request $request){
        $photo = DB::select("SELECT * FROM yakopi_photo_research LIMIT 10");
        return [
            "success"=>true,
            "data"=>$photo
        ];
    });

    // PHOTO API END

    // PROJECT API START

    Route::get("/project", function (Request $request){
        $project = DB::select("SELECT * FROM yakopi_project");
        return [
            "success"=>true,
            "data"=>$project
        ];
    });

    Route::get("/project/{id}", function (Request $request, $id){
        $project = DB::select("SELECT * FROM yakopi_project WHERE id_project=?",[$id]);
        return [
            "success"=>true,
            "data"=>$project
        ];
    });

    // PROJECT API END

    // GENERAL API END

    // CUTI START

    Route::get("/kategori-cuti", function (Request $request){
        $kategori = DB::select("SELECT * FROM yakopi_kategori_cuti");
        return [
            "success"=>true,
            "data"=>$kategori
        ];
    });

    Route::get("/cuti", function (Request $request){
        $token = $request->bearerToken();
    
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $cuti = DB::select("SELECT * FROM yakopi_cuti 
        LEFT JOIN yakopi_pengguna ON yakopi_pengguna.id_pengguna=yakopi_cuti.created_by
        LEFT JOIN yakopi_kategori_cuti ON yakopi_kategori_cuti.id_kategori_cuti=yakopi_cuti.id_kategori_cuti
        ");
        return [
            "success"=>true,
            "data"=>$cuti
        ];
    });

    Route::post("/get-cuti", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $json->id_pengguna;

        $cuti = DB::select("SELECT * FROM yakopi_cuti 
        LEFT JOIN yakopi_pengguna ON yakopi_pengguna.id_pengguna=yakopi_cuti.created_by
        LEFT JOIN yakopi_kategori_cuti ON yakopi_kategori_cuti.id_kategori_cuti=yakopi_cuti.id_kategori_cuti
        WHERE yakopi_cuti.created_by=?",[$id]);
        return [
            "success"=>true,
            "data"=>$cuti
        ];
    });

    Route::post("/cuti", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $tanggal_mulai_cuti = $request->tanggal_mulai_cuti;
        $tanggal_selesai_cuti = $request->tanggal_selesai_cuti;
        $keterangan_cuti = $request->keterangan_cuti;
        $status_cuti = 'Pending';
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");
        $id_kategori_cuti = $request->id_kategori_cuti;

        $cuti = DB::insert("INSERT INTO yakopi_cuti (id_cuti,tanggal_mulai_cuti, tanggal_selesai_cuti, keterangan_cuti, status_cuti, created_by, created_time, id_kategori_cuti) 
        VALUES (null,?,?,?,?,?,?,?)",[$tanggal_mulai_cuti, $tanggal_selesai_cuti, $keterangan_cuti, $status_cuti, $created_by, $created_time, $id_kategori_cuti]);

        return [
            "success"=>true,
            "data"=>$cuti
        ];
    });

    Route::delete("/delete-cuti", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_cuti = $request->id_cuti;

        $selectCuti = DB::select("SELECT * FROM yakopi_cuti WHERE id_cuti=?",[$id_cuti]);

        if($selectCuti){
            $cuti = DB::delete("DELETE FROM yakopi_cuti WHERE id_cuti=?",[$id_cuti]);
            return [
                "success"=>true,
                "id"=>$id_cuti,
                "data"=>$selectCuti,
                "message"=>"Cuti berhasil dihapus"
            ];
        }else{
            return [
                "success"=>false,
                "id"=>$id_cuti,
                "data"=>$selectCuti,
                "message"=>"Cuti tidak ditemukan"
            ];
        }
    });

    Route::put("/pengajuan-cuti", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_cuti;
        $status_cuti = 'Diajukan';

        $cuti = DB::update("UPDATE yakopi_cuti SET status_cuti=? WHERE id_cuti=?",[$status_cuti, $id]);
        
        return [
            "success"=>true,
            "data"=>$cuti
        ];
    });


    // PRESENSI START

    Route::post("/cek-presensi", function (Request $request){
        $token = $request->bearerToken();

        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $presensi_today = DB::select("SELECT * FROM yakopi_absen WHERE tgl_absen=CURDATE() AND id_pengguna=?",[$json->id_pengguna]);
        
        if(count($presensi_today)==0){

            return [
                "success"=>true,
                "data"=>[
                    "jam_masuk_absen"=>null,
                    "jam_keluar_absen"=>null
                ]
                ];
            
        }
        else{
            return [
                "success"=>true,
                "data"=>[
                    "jam_masuk_absen"=>$presensi_today[0]->jam_masuk_absen,
                    "jam_keluar_absen"=>$presensi_today[0]->jam_keluar_absen
                ]
                ];
        }
    });

    Route::post("/presensi-pulang", function(Request $request){
        $token = $request->bearerToken();
    
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $filename = $request->filename;
        $timezone = $request->timezone;
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $updatepresensi = DB::insert("UPDATE yakopi_absen SET jam_keluar_absen=?,foto_absen_keluar=?, lat_absen_keluar=?, long_absen_keluar=? WHERE id_pengguna=? AND tgl_absen=CURDATE()",[$timezone,"assets/img/absenKeluar/".$filename,$latitude,$longitude,$json->id_pengguna]);
    
        return [
            "success"=>true,
            "msg"=>"Berhasil melakukan presensi pulang"
        ];
    });

    Route::post("/presensi-masuk", function(Request $request){
        $token = $request->bearerToken();
    
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $filename = $request->filename;
        $timezone = $request->timezone;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
    
        $checkexist = DB::select("SELECT * FROM yakopi_absen WHERE tgl_absen=CURDATE() AND id_pengguna=?",[$json->id_pengguna]);
        
        if(count($checkexist)==0){
            $insertpresensimasuk = DB::insert("INSERT INTO yakopi_absen VALUES (NULL,?,NOW(),?,NULL,?,NULL,?,?,NULL,NULL)",[$json->id_pengguna,$timezone,"assets/img/absenMasuk/".$filename,$latitude,$longitude]);
    
            return [
                "success"=>true,
                "msg"=>"Berhasil melakukan presensi"
            ];
        }
        else{
            return [
                "success"=>false,
                "msg"=>"Sudah melakukan presensi"
            ];
        }
    });

    // PRESENSI END

    // PROFILE START

    Route::get("/profile", function (Request $request){
        $token = $request->bearerToken();

        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $profile = DB::select("SELECT * FROM yakopi_pengguna WHERE id_pengguna=?",[$json->id_pengguna]);
        return [
            "success"=>true,
            "data"=>$profile[0]
        ];
    });

    Route::post("/update-profile", function (Request $request){
        $token = $request->bearerToken();

        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $username = $request->username;
        $namalengkap = $request->namalengkap;
        $jenkel = $request->jenkel;
        $tanggallahir = $request->tanggallahir;
        $alamat = $request->alamat;
        $email = $request->email;
        $notelepon = $request->notelepon;
        $newpassword = $request->newpassword;
        $fotoprofil = $request->fotoprofil;

        if($newpassword==""){
            $newpassword = $json->password;
        }
        else{
            $newpassword = sha1($request->newpassword);
        }
    
    
        $update = DB::update("UPDATE yakopi_pengguna SET password=?,email=?,no_hp=?,nama_lengkap=?,foto_pengguna=?,jenkel=?,tgl_lahir=?,alamat=? 
        WHERE id_pengguna=?",[$newpassword,$email,$notelepon,$namalengkap,$fotoprofil,$jenkel,$tanggallahir,$alamat,$json->id_pengguna]);

        $newdata = DB::select("SELECT * FROM yakopi_pengguna WHERE id_pengguna=?",[$json->id_pengguna]);

        $modul = DB::select("SELECT * FROM yakopi_hak_akses WHERE nama_hak_akses=?",[$newdata[0]->hak_akses]);

        return [
            "success"=>true,
            "msg"=>"Berhasil mengubah profil",
            "credentials"=>[
                "data"=>$newdata[0],
                "modul"=>$modul[0],
                "token"=>Crypt::encryptString(json_encode($newdata[0]))
            ]
        ];

    });

    // PROFILE END

    // GENERAL API END

    // RESTORATION START

    // LAND ASSESSMENT START
    Route::get("/land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $land_assessment = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_land_assessment AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");
        return [
            "success"=>true,
            "data"=>$land_assessment
        ];
    });

    Route::get("/land-assessment/{id}", function (Request $request, $id){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $land_assessment = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_land_assessment AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_land_assessment=?
        ",[$id]);
        return [
            "success"=>true,
            "data"=>$land_assessment[0]
        ];
    });

    Route::get("/history-land-assessment/{id}", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $land_assessment = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_land_assessment AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.created_by=?
        ",[$json->id_pengguna]);
        return [
            "success"=>true,
            "data"=>$land_assessment
        ];
    });

    Route::post("/land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $site_code = $request->site_code;
        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $date_land_assessment = $request->date_land_assessment;
        $lat_land_assessment = $request->coordinate["latitude"];
        $long_land_assessment = $request->coordinate["longitude"];
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $posisi_site = $request->site_position;
        $perkiraan_jumlah_plot = $request->estimated_number_of_plots_per_site;
        $sejarah_lokasi = $request->history_location;
        $akses_jalan = $request->road_access;
        $kondisi_lahan = $request->land_condition;
        $tegakan_mangrove = $request->the_presence_of_mangrove_stands;
        $adanya_perdu = $request->shrubs;
        $potensi_gangguan_hewan_peliharaan = $request->pet_nuisance;
        $potensi_hama = $request->pest_potential;
        $potensi_gangguan_tritip = $request->tritype_disorder_potential;
        $potensi_gangguan_kepiting = $request->crab_interference_potential;
        $potensi_gempuran_ombak = $request->potential_for_waves;
        $jenis_tanah = $request->type_of_soil;
        $catatan_khusus_1 = $request->important_information_from_group_members;
        $catatan_khusus_2 = $request->other_important_information_from_group_members;
        $nama_surveyor = $request->surveyor;
        $ttd_surveyor = "";
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");
        $status = 0;

        $land_assessment = DB::insert("INSERT INTO yakopi_land_assessment (id_land_assessment,site_code,id_project,id_provinces,id_cities,id_districts,date_land_assessment,lat_land_assessment,long_land_assessment,nama_desa,nama_dusun,posisi_site,perkiraan_jumlah_plot,sejarah_lokasi,akses_jalan,kondisi_lahan,tegakan_mangrove,adanya_perdu,potensi_gangguan_hewan_peliharaan,potensi_hama,potensi_gangguan_tritip,potensi_gangguan_kepiting,potensi_gempuran_ombak,jenis_tanah,catatan_khusus_1,catatan_khusus_2,nama_surveyor,ttd_surveyor,created_by,created_time,status) VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",[$site_code,$id_project,$id_provinces,$id_cities,$id_districts,$date_land_assessment,$lat_land_assessment,$long_land_assessment,$nama_desa,$nama_dusun,$posisi_site,$perkiraan_jumlah_plot,$sejarah_lokasi,$akses_jalan,$kondisi_lahan,$tegakan_mangrove,$adanya_perdu,$potensi_gangguan_hewan_peliharaan,$potensi_hama,$potensi_gangguan_tritip,$potensi_gangguan_kepiting,$potensi_gempuran_ombak,$jenis_tanah,$catatan_khusus_1,$catatan_khusus_2,$nama_surveyor,$ttd_surveyor,$created_by,$created_time,$status]);
        return [
            "success"=>true,
            "msg"=>"Berhasil menambahkan land assessment"
        ];
    });

    Route::post("/approve-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;
        $status = 1;

        $land_assessment = DB::update("UPDATE yakopi_land_assessment SET status=? WHERE id_land_assessment=?",[$status,$id_land_assessment]);

        return [
            "success"=>true,
            "msg"=>"Berhasil mengkonfirmasi land assessment"
        ];
    });

    Route::post("/reject-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;
        $status = 2;

        $land_assessment = DB::update("UPDATE yakopi_land_assessment SET status=? WHERE id_land_assessment=?",[$status,$id_land_assessment]);

        return [
            "success"=>true,
            "msg"=>"Berhasil menolak land assessment"
        ];
    });

    Route::post("/photo-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;

        $photo_land_assessment = DB::select("SELECT * FROM yakopi_land_assessment_photo WHERE id_land_assessment=?",[$id_land_assessment]);
        return [
            "success"=>true,
            "data"=>$photo_land_assessment
        ];
    });

    Route::post("/add-photo-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;
        $keterangan_land_assessment_photo = $request->keterangan_land_assessment_photo;
        $link_land_assessment_photo = $request->link_land_assessment_photo;
        $file_land_assessment_photo = $request->file_land_assessment_photo;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $photo_land_assessment = DB::insert("INSERT INTO yakopi_land_assessment_photo (id_land_assessment_photo,id_land_assessment,keterangan_land_assessment_photo,link_land_assessment_photo,file_land_assessment_photo,created_by,created_time) VALUES (null,?,?,?,?,?,?)",[$id_land_assessment,$keterangan_land_assessment_photo,$link_land_assessment_photo,$file_land_assessment_photo,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Berhasil menambahkan photo land assessment"
        ];
    });

    Route::delete("/delete-photo-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment_photo = $request->id_land_assessment_photo;

        $cekIdLandAssessment = DB::select("SELECT * FROM yakopi_land_assessment_photo WHERE id_land_assessment_photo=?",[$id_land_assessment_photo]);

        if(count($cekIdLandAssessment)>0){
            $id_land_assessment = $cekIdLandAssessment[0]->id_land_assessment;

            $cekStatus = DB::select("SELECT * FROM yakopi_land_assessment WHERE id_land_assessment=?",[$id_land_assessment]);

            if($cekStatus[0]->status=="0"){
                $photo_land_assessment = DB::delete("DELETE FROM yakopi_land_assessment_photo WHERE id_land_assessment_photo=?",[$id_land_assessment_photo]);
            
                return [
                    "success"=>true,
                    "msg"=>"Berhasil menghapus photo land assessment"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Gagal menghapus photo land assessment. Land assessment sudah di konfirmasi"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Gagal menghapus photo land assessment. Data tidak ditemukan"
            ];
        }
    });

    Route::post("/video-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;

        $video_land_assessment = DB::select("SELECT * FROM yakopi_land_assessment_video WHERE id_land_assessment=?",[$id_land_assessment]);
        return [
            "success"=>true,
            "data"=>$video_land_assessment
        ];
    });

    Route::post("/add-video-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;
        $keterangan_land_assessment_video = $request->keterangan_land_assessment_video;
        $link_land_assessment_video = $request->link_land_assessment_video;
        $file_land_assessment_video = $request->file_land_assessment_video;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $video_land_assessment = DB::insert("INSERT INTO yakopi_land_assessment_video (id_land_assessment_video,id_land_assessment,keterangan_land_assessment_video,link_land_assessment_video,file_land_assessment_video,created_by,created_time) VALUES (null,?,?,?,?,?,?)",[$id_land_assessment,$keterangan_land_assessment_video,$link_land_assessment_video,$file_land_assessment_video,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Berhasil menambahkan video land assessment"
        ];
    });

    Route::delete("/delete-video-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment_video = $request->id_land_assessment_video;

        $cekIdLandAssessment = DB::select("SELECT * FROM yakopi_land_assessment_video WHERE id_land_assessment_video=?",[$id_land_assessment_video]);

        if(count($cekIdLandAssessment)>0){
            $id_land_assessment = $cekIdLandAssessment[0]->id_land_assessment;

            $cekStatus = DB::select("SELECT * FROM yakopi_land_assessment WHERE id_land_assessment=?",[$id_land_assessment]);

            if($cekStatus[0]->status=="0"){
                $video_land_assessment = DB::delete("DELETE FROM yakopi_land_assessment_video WHERE id_land_assessment_video=?",[$id_land_assessment_video]);
            
                return [
                    "success"=>true,
                    "msg"=>"Berhasil menghapus video land assessment"
                ];

            }else{
                return [
                    "success"=>false,
                    "msg"=>"Gagal menghapus video land assessment. Land assessment sudah di konfirmasi"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Gagal menghapus video land assessment. Data tidak ditemukan"
            ];
        }

    });

    Route::post("/drone-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;

        $drone_land_assessment = DB::select("SELECT * FROM yakopi_land_assessment_drone WHERE id_land_assessment=?",[$id_land_assessment]);
        return [
            "success"=>true,
            "data"=>$drone_land_assessment
        ];
    });

    Route::post("/add-drone-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;
        $keterangan_land_assessment_drone = $request->keterangan_land_assessment_drone;
        $link_land_assessment_drone = $request->link_land_assessment_drone;
        $file_land_assessment_drone = $request->file_land_assessment_drone;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $drone_land_assessment = DB::insert("INSERT INTO yakopi_land_assessment_drone (id_land_assessment_drone,id_land_assessment,keterangan_land_assessment_drone,link_land_assessment_drone,file_land_assessment_drone,created_by,created_time) VALUES (null,?,?,?,?,?,?)",[$id_land_assessment,$keterangan_land_assessment_drone,$link_land_assessment_drone,$file_land_assessment_drone,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Berhasil menambahkan drone land assessment"
        ];

    });

    Route::delete("/delete-drone-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment_drone = $request->id_land_assessment_drone;

        $cekIdLandAssessment = DB::select("SELECT * FROM yakopi_land_assessment_drone WHERE id_land_assessment_drone=?",[$id_land_assessment_drone]);

        if(count($cekIdLandAssessment)>0){
            $id_land_assessment = $cekIdLandAssessment[0]->id_land_assessment;

            $cekStatus = DB::select("SELECT * FROM yakopi_land_assessment WHERE id_land_assessment=?",[$id_land_assessment]);

            if($cekStatus[0]->status=="0"){
                $drone_land_assessment = DB::delete("DELETE FROM yakopi_land_assessment_drone WHERE id_land_assessment_drone=?",[$id_land_assessment_drone]);
            
                return [
                    "success"=>true,
                    "msg"=>"Berhasil menghapus drone land assessment"
                ];

            }else{
                return [
                    "success"=>false,
                    "msg"=>"Gagal menghapus drone land assessment. Land assessment sudah di konfirmasi"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Gagal menghapus drone land assessment. Data tidak ditemukan"
            ];
        }

    });

    Route::delete("/delete-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;
        $cekLandAssessment = DB::select("SELECT * FROM yakopi_land_assessment WHERE id_land_assessment=?",[$id_land_assessment]);

        if(count($cekLandAssessment) > 0){
            if($cekLandAssessment[0]->status == 0){
                $land_assessment = DB::delete("DELETE FROM yakopi_land_assessment WHERE id_land_assessment=?",[$id_land_assessment]);
                return [
                    "success"=>true,
                    "msg"=>"Berhasil menghapus land assessment"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Land assessment sudah di konfirmasi"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Land assessment tidak ditemukan"
            ];
        }
    });

    // LAND ASSESSMENT FINISH

    // SEED COLLECTING START

    Route::get("/seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $seed_collecting = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_collecting_seed AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");

        return [
            "success"=>true,
            "data"=>$seed_collecting
        ];
    });

    Route::get("/seed-collecting/{id}", function (Request $request,$id){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $seed_collecting = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_collecting_seed AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_collecting_seed=?
        ",[$id]);

        $detail_collecting_seed = DB::select("
        SELECT * FROM yakopi_detail_collecting_seed WHERE id_collecting_seed=?",[$id]);

        return [
            "success"=>true,
            "dataSeedCollecting"=>$seed_collecting[0],
            "dataDetailCollectingSeed"=>$detail_collecting_seed[0]
        ];
    });

    Route::get("/history-seed-collecting/{id_pengguna}", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $history_seed_collecting = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_collecting_seed AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.created_by=?
        ",[$json->id_pengguna]);

        return [
            "success"=>true,
            "data"=>$history_seed_collecting
        ];
    });

    Route::post("/seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $lat_collecting_seed = $request->coordinate["latitude"];
        $long_collecting_seed = $request->coordinate["longitude"];
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $trasportasi_1 = $request->transportation_used_1;
        $trasportasi_2 = $request->transportation_used_2;
        $catatan_1 = $request->important_information_from_group_members;
        $catatan_2 = $request->other_important_information_from_group_members;
        $dilaporkan_oleh = $request->dilaporkan_oleh;
        $ttd_pelapor = '';
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $seedCollecting = DB::insert("INSERT INTO yakopi_collecting_seed (id_collecting_seed,id_project,id_provinces,id_cities,id_districts,lat_collecting_seed,long_collecting_seed,nama_desa,nama_dusun,trasportasi_1,trasportasi_2,catatan_1,catatan_2,dilaporkan_oleh,ttd_pelapor,created_by,created_time)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        ,[null,$id_project,$id_provinces,$id_cities,$id_districts,$lat_collecting_seed,$long_collecting_seed,$nama_desa,$nama_dusun,$trasportasi_1,$trasportasi_2,$catatan_1,$catatan_2,$dilaporkan_oleh,$ttd_pelapor,$created_by,$created_time]);
        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::post("/approve-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;
        $status = 1;

        $approveSeedCollecting = DB::update("UPDATE yakopi_collecting_seed SET status=? WHERE id_collecting_seed=?",[$status,$id_collecting_seed]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Mengkonfirmasi Data"
        ];
    });

    Route::post("/reject-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;
        $status = 2;

        $rejectSeedCollecting = DB::update("UPDATE yakopi_collecting_seed SET status=? WHERE id_collecting_seed=?",[$status,$id_collecting_seed]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Menolak Data"
        ];
    });

    Route::post("/kind-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;

        $kind_seed_collecting = DB::select("SELECT * FROM yakopi_detail_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);

        return [
            "success"=>true,
            "data"=>$kind_seed_collecting
        ];
    });

    Route::post("/add-kind-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;
        $tanggal_collecting = $request->tanggal_collecting;
        $jumlah_pekerja = $request->jumlah_pekerja;
        $pria = $request->pria;
        $wanita = $request->wanita;
        $r_mucronoto = $request->r_mucronoto;
        $r_styloso = $request->r_styloso;
        $r_apiculata = $request->r_apiculata;
        $avicennia_spp = $request->avicennia_spp;
        $ceriops_spp = $request->ceriops_spp;
        $xylocarpus_spp = $request->xylocarpus_spp;
        $bruguiera_spp = $request->bruguiera_spp;
        $sonneratia_spp = $request->sonneratia_spp;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $kindSeedCollecting = DB::insert("INSERT INTO yakopi_detail_collecting_seed (id_detail_collecting_seed,id_collecting_seed,tanggal_collecting,jumlah_pekerja,pria,wanita,r_mucronoto,r_styloso,r_apiculata,avicennia_spp,ceriops_spp,xylocarpus_spp,bruguiera_spp,sonneratia_spp,created_by,created_time)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        ,[null,$id_collecting_seed,$tanggal_collecting,$jumlah_pekerja,$pria,$wanita,$r_mucronoto,$r_styloso,$r_apiculata,$avicennia_spp,$ceriops_spp,$xylocarpus_spp,$bruguiera_spp,$sonneratia_spp,$created_by,$created_time]);
        
        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];
    });

    Route::delete("/delete-kind-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_detail_collecting_seed = $request->id_detail_collecting_seed;
        
        $cekIdCollection = DB::select("SELECT * FROM yakopi_detail_collecting_seed WHERE id_detail_collecting_seed=?",[$id_detail_collecting_seed]);
        if(count($cekIdCollection)>0){
            $id_collecting_seed = $cekIdCollection[0]->id_collecting_seed;

            $cekStatus = DB::select("SELECT * FROM yakopi_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_detail_collecting_seed WHERE id_detail_collecting_seed=?",[$id_detail_collecting_seed]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/photo-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;

        $photo_seed_collecting = DB::select("SELECT * FROM yakopi_collecting_seed_photo WHERE id_collecting_seed=?",[$id_collecting_seed]);

        return [
            "success"=>true,
            "data"=>$photo_seed_collecting
        ];
    });

    Route::post("/add-photo-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;
        $keterangan_collecting_seed_photo = $request->keterangan_collecting_seed_photo;
        $link_collecting_seed_photo = $request->link_collecting_seed_photo;
        $file_collecting_seed_photo = $request->file_collecting_seed_photo;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $photoSeedCollecting = DB::insert("INSERT INTO yakopi_collecting_seed_photo (id_collecting_seed_photo,id_collecting_seed,keterangan_collecting_seed_photo,link_collecting_seed_photo,file_collecting_seed_photo,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id_collecting_seed,$keterangan_collecting_seed_photo,$link_collecting_seed_photo,$file_collecting_seed_photo,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-photo-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed_photo = $request->id_collecting_seed_photo;

        $cekIdCollection = DB::select("SELECT * FROM yakopi_collecting_seed_photo WHERE id_collecting_seed_photo=?",[$id_collecting_seed_photo]);

        if(count($cekIdCollection)>0){
            $id_collecting_seed = $cekIdCollection[0]->id_collecting_seed;

            $cekStatus = DB::select("SELECT * FROM yakopi_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_collecting_seed_photo WHERE id_collecting_seed_photo=?",[$id_collecting_seed_photo]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/video-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;

        $videoSeedCollecting = DB::select("SELECT * FROM yakopi_collecting_seed_video WHERE id_collecting_seed=?",[$id_collecting_seed]);

        return [
            "success"=>true,
            "data"=>$videoSeedCollecting
        ];
    });

    Route::post("/add-video-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;
        $keterangan_collecting_seed_video = $request->keterangan_collecting_seed_video;
        $link_collecting_seed_video = $request->link_collecting_seed_video;
        $file_collecting_seed_video = $request->file_collecting_seed_video;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $videoSeedCollecting = DB::insert("INSERT INTO yakopi_collecting_seed_video (id_collecting_seed_video,id_collecting_seed,keterangan_collecting_seed_video,link_collecting_seed_video,file_collecting_seed_video,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id_collecting_seed,$keterangan_collecting_seed_video,$link_collecting_seed_video,$file_collecting_seed_video,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-video-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed_video = $request->id_collecting_seed_video;

        $cekIdCollection = DB::select("SELECT * FROM yakopi_collecting_seed_video WHERE id_collecting_seed_video=?",[$id_collecting_seed_video]);

        if(count($cekIdCollection)>0){
            $id_collecting_seed = $cekIdCollection[0]->id_collecting_seed;

            $cekStatus = DB::select("SELECT * FROM yakopi_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_collecting_seed_video WHERE id_collecting_seed_video=?",[$id_collecting_seed_video]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/drone-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;

        $droneSeedCollecting = DB::select("SELECT * FROM yakopi_collecting_seed_drone WHERE id_collecting_seed=?",[$id_collecting_seed]);
        
        return [
            "success"=>true,
            "data"=>$droneSeedCollecting
        ];

    });

    Route::post("/add-drone-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;
        $keterangan_collecting_seed_drone = $request->keterangan_collecting_seed_drone;
        $link_collecting_seed_drone = $request->link_collecting_seed_drone;
        $file_collecting_seed_drone = $request->file_collecting_seed_drone;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $droneSeedCollecting = DB::insert("INSERT INTO yakopi_collecting_seed_drone (id_collecting_seed_drone,id_collecting_seed,keterangan_collecting_seed_drone,link_collecting_seed_drone,file_collecting_seed_drone,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id_collecting_seed,$keterangan_collecting_seed_drone,$link_collecting_seed_drone,$file_collecting_seed_drone,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-drone-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed_drone = $request->id_collecting_seed_drone;

        $cekIdCollection = DB::select("SELECT * FROM yakopi_collecting_seed_drone WHERE id_collecting_seed_drone=?",[$id_collecting_seed_drone]);

        if(count($cekIdCollection)>0){
            $id_collecting_seed = $cekIdCollection[0]->id_collecting_seed;

            $cekStatus = DB::select("SELECT * FROM yakopi_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_collecting_seed_drone WHERE id_collecting_seed_drone=?",[$id_collecting_seed_drone]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::delete("/delete-collecting-seed", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;

        $cekIdCollection = DB::select("SELECT * FROM yakopi_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);

        if(count($cekIdCollection)>0){
            $id_collecting_seed = $cekIdCollection[0]->id_collecting_seed;

            $cekStatus = DB::select("SELECT * FROM yakopi_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    // SEED COLLECTING END

    // NURSERY ACTIVITY START

    Route::get("/nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $nurseryActivity = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_nursery_activity AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");

        return [
            "success"=>true,
            "data"=>$nurseryActivity
        ];

    });

    Route::get("/nursery-activity/{id_nursery_activity}", function (Request $request,$id_nursery_activity){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $nurseryActivity = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_nursery_activity AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_nursery_activity=?
        ",[$id_nursery_activity]);

        $detail_nursery_activity = DB::select("
        SELECT * FROM yakopi_detail_nursery_activity WHERE id_nursery_activity=?",[$id_nursery_activity]);

        return [
            "success"=>true,
            "data"=>$nurseryActivity,
            "detail_nursery_activity"=>$detail_nursery_activity
        ];

    });

    Route::get("/history-nursery-activity/{id_pengguna}", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $nurseryActivity = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_nursery_activity AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.created_by=?
        ",[$json->id_pengguna]);

        return [
            "success"=>true,
            "data"=>$nurseryActivity
        ];
    });

    Route::post("/nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $lat_nursery_activity = $request->coordinate["latitude"];
        $long_nursery_activity = $request->coordinate["longitude"];
        $catatan_1 = $request->catatan_1;
        $catatan_2 = $request->catatan_2;
        $dilaporkan_oleh = $request->dilaporkan_oleh;
        $ttd_pelapor = "";
        $created_by = $json->id_pengguna;  
        $created_time = date("Y-m-d H:i:s");
        $status = 0;

        $nurseryActivity = DB::insert(' INSERT INTO yakopi_nursery_activity (id_project,id_provinces,id_cities,id_districts,lat_nursery_activity,long_nursery_activity,nama_desa,nama_dusun,catatan_1,catatan_2,dilaporkan_oleh,ttd_pelapor,created_by,created_time,status) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        ,[$id_project,$id_provinces,$id_cities,$id_districts,$lat_nursery_activity,$long_nursery_activity,$nama_desa,$nama_dusun,$catatan_1,$catatan_2,$dilaporkan_oleh,$ttd_pelapor,$created_by,$created_time,$status]);
    
        if($nurseryActivity){
            return [
                "success"=>true,
                "msg"=>"Data berhasil ditambahkan"
            ];
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat ditambahkan"
            ];
        }
    });

    Route::post("/approve-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_nursery_activity = $request->id_nursery_activity;
        $status = 1;

        $approveSeedCollecting = DB::update("UPDATE yakopi_nursery_activity SET status=? WHERE id_nursery_activity=?",[$status,$id_nursery_activity]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Mengkonfirmasi Data"
        ];
    });

    Route::post("/reject-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_nursery_activity = $request->id_nursery_activity;
        $status = 2;

        $rejectSeedCollecting = DB::update("UPDATE yakopi_nursery_activity SET status=? WHERE id_nursery_activity=?",[$status,$id_nursery_activity]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Menolak Data"
        ];
    });

    Route::post("/kind-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_nursery_activity = $request->id_nursery_activity;

        $kind_nursery_activity = DB::select("SELECT * FROM yakopi_detail_nursery_activity WHERE id_nursery_activity=?",[$id_nursery_activity]);

        return [
            "success"=>true,
            "data"=>$kind_nursery_activity
        ];
    });

    Route::post("/add-kind-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_nursery_activity = $request->id_nursery_activity;
        $tanggal_collecting = $request->tanggal_collecting;
        $jumlah_pekerja = $request->jumlah_pekerja;
        $pria = $request->pria;
        $wanita = $request->wanita;
        $r_mucronoto = $request->r_mucronoto;
        $r_styloso = $request->r_styloso;
        $r_apiculata = $request->r_apiculata;
        $avicennia_spp = $request->avicennia_spp;
        $ceriops_spp = $request->ceriops_spp;
        $xylocarpus_spp = $request->xylocarpus_spp;
        $bruguiera_spp = $request->bruguiera_spp;
        $sonneratia_spp = $request->sonneratia_spp;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $kindSeedCollecting = DB::insert("INSERT INTO yakopi_detail_nursery_activity (id_detail_nursery_activity,id_nursery_activity,tanggal_collecting,jumlah_pekerja,pria,wanita,r_mucronoto,r_styloso,r_apiculata,avicennia_spp,ceriops_spp,xylocarpus_spp,bruguiera_spp,sonneratia_spp,created_by,created_time)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        ,[null,$id_nursery_activity,$tanggal_collecting,$jumlah_pekerja,$pria,$wanita,$r_mucronoto,$r_styloso,$r_apiculata,$avicennia_spp,$ceriops_spp,$xylocarpus_spp,$bruguiera_spp,$sonneratia_spp,$created_by,$created_time]);
        
        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];
    });

    Route::delete("/delete-kind-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_detail_nursery_activity = $request->id_detail_nursery_activity;
        
        $cekId = DB::select("SELECT * FROM yakopi_detail_nursery_activity WHERE id_detail_nursery_activity=?",[$id_detail_nursery_activity]);
        if(count($cekId)>0){
            $id_nursery_activity = $cekId[0]->id_nursery_activity;

            $cekStatus = DB::select("SELECT * FROM yakopi_nursery_activity WHERE id_nursery_activity=?",[$id_nursery_activity]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_detail_nursery_activity WHERE id_detail_nursery_activity=?",[$id_detail_nursery_activity]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/photo-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity;

        $photo = DB::select("SELECT * FROM yakopi_nursery_activity_photo WHERE id_nursery_activity=?",[$id]);

        return [
            "success"=>true,
            "data"=>$photo
        ];
    });

    Route::post("/add-photo-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity;
        $keterangan = $request->keterangan_nursery_activity_photo;
        $link = $request->link_nursery_activity_photo;
        $file = $request->file_nursery_activity_photo;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $photo = DB::insert("INSERT INTO yakopi_nursery_activity_photo (id_nursery_activity_photo,id_nursery_activity,keterangan_nursery_activity_photo,link_nursery_activity_photo,file_nursery_activity_photo,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-photo-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity_photo;

        $cekId = DB::select("SELECT * FROM yakopi_nursery_activity_photo WHERE id_nursery_activity_photo=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_nursery_activity;

            $cekStatus = DB::select("SELECT * FROM yakopi_nursery_activity WHERE id_nursery_activity=?",[$id1]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_nursery_activity_photo WHERE id_nursery_activity_photo=?",[$id]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/video-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity;

        $video = DB::select("SELECT * FROM yakopi_nursery_activity_video WHERE id_nursery_activity=?",[$id]);

        return [
            "success"=>true,
            "data"=>$video
        ];
    });

    Route::post("/add-video-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity;
        $keterangan = $request->keterangan_nursery_activity_video;
        $link = $request->link_nursery_activity_video;
        $file = $request->file_nursery_activity_video;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $video = DB::insert("INSERT INTO yakopi_nursery_activity_video (id_nursery_activity_video,id_nursery_activity,keterangan_nursery_activity_video,link_nursery_activity_video,file_nursery_activity_video,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-video-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity_video;

        $cekId = DB::select("SELECT * FROM yakopi_nursery_activity_video WHERE id_nursery_activity_video=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_nursery_activity;

            $cekStatus = DB::select("SELECT * FROM yakopi_nursery_activity WHERE id_nursery_activity=?",[$id1]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_nursery_activity_video WHERE id_nursery_activity_video=?",[$id]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/drone-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity;

        $drone = DB::select("SELECT * FROM yakopi_nursery_activity_drone WHERE id_nursery_activity=?",[$id]);
        
        return [
            "success"=>true,
            "data"=>$drone
        ];

    });

    Route::post("/add-drone-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity;
        $keterangan = $request->keterangan_nursery_activity_drone;
        $link = $request->link_nursery_activity_drone;
        $file = $request->file_nursery_activity_drone;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $drone = DB::insert("INSERT INTO yakopi_nursery_activity_drone (id_nursery_activity_drone,id_nursery_activity,keterangan_nursery_activity_drone,link_nursery_activity_drone,file_nursery_activity_drone,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-drone-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity_drone;

        $cekId = DB::select("SELECT * FROM yakopi_nursery_activity_drone WHERE id_nursery_activity_drone=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_nursery_activity;

            $cekStatus = DB::select("SELECT * FROM yakopi_nursery_activity WHERE id_nursery_activity=?",[$id1]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_nursery_activity_drone WHERE id_nursery_activity_drone=?",[$id]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::delete("/delete-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity;

        $cekId = DB::select("SELECT * FROM yakopi_nursery_activity WHERE id_nursery_activity=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_nursery_activity;

            $cekStatus = DB::select("SELECT * FROM yakopi_nursery_activity WHERE id_nursery_activity=?",[$id1]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_nursery_activity WHERE id_nursery_activity=?",[$id1]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    // NURSERY ACTIVITY END

    // PLANTING ACTION START

    Route::get("/planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_planting_action;

        $planting = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_planting_action AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");

        return [
            "success"=>true,
            "data"=>$planting
        ];

    });

    Route::get("/planting-action/{id_planting_action}", function (Request $request,$id_planting_action){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $plantingAction = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_planting_action AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_planting_action=?
        ",[$id_planting_action]);

        $detailPlantingAction = DB::select("
        SELECT * FROM yakopi_detail_planting_action WHERE id_planting_action=?",[$id_planting_action]);

        return [
            "success"=>true,
            "data"=>$plantingAction,
            "detail_planting_action"=>$detailPlantingAction
        ];

    });

    Route::get("/history-planting-action/{id_pengguna}", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $plantingAction = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_planting_action AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.created_by=?
        ",[$json->id_pengguna]);

        return [
            "success"=>true,
            "data"=>$plantingAction
        ];
    });

    Route::post("/planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $jarak_tanam = $request->jarak_tanam;
        $lokasi_tanam = $request->lokasi_tanam;
        $info_1 = $request->info_1;
        $transportation = $request->transportation;
        $catatan_1 = $request->catatan_1;
        $catatan_2 = $request->catatan_2;
        $dilaporkan_oleh = $request->dilaporkan_oleh;
        $ttd_pelapor = "";
        $created_by = $json->id_pengguna;  
        $created_time = date("Y-m-d H:i:s");
        $status = 0;

        $plantingAction = DB::insert(" INSERT INTO yakopi_planting_action (id_planting_action,id_project,id_provinces,id_cities,id_districts,nama_desa,nama_dusun,jarak_tanam,lokasi_tanam,info_1,transportation,catatan_1,catatan_2,dilaporkan_oleh,ttd_pelapor,created_by,created_time,status)
        VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ",[$id_project,$id_provinces,$id_cities,$id_districts,$nama_desa,$nama_dusun,$jarak_tanam,$lokasi_tanam,$info_1,$transportation,$catatan_1,$catatan_2,$dilaporkan_oleh,$ttd_pelapor,$created_by,$created_time,$status]);
        if($plantingAction){
            return [
                "success"=>true,
                "msg"=>"Data berhasil ditambahkan"
            ];
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat ditambahkan"
            ];
        }
    });

    Route::post("/approve-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_planting_action = $request->id_planting_action;
        $status = 1;

        $approvePlantingAction = DB::update("UPDATE yakopi_planting_action SET status=? WHERE id_planting_action=?",[$status,$id_planting_action]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Mengkonfirmasi Data"
        ];
    });

    Route::post("/reject-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_planting_action = $request->id_planting_action;
        $status = 2;

        $rejectPlantingAction = DB::update("UPDATE yakopi_planting_action SET status=? WHERE id_planting_action=?",[$status,$id_planting_action]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Menolak Data"
        ];
    });

    Route::post("/kind-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_planting_action = $request->id_planting_action;

        $kind_planting_action = DB::select("SELECT * FROM yakopi_detail_planting_action WHERE id_planting_action=?",[$id_planting_action]);

        return [
            "success"=>true,
            "data"=>$kind_planting_action
        ];
    });

    Route::post("/add-kind-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_planting_action = $request->id_planting_action;
        $date_planting_action = $request->date_planting_action;
        $jumlah_pekerja = $request->jumlah_pekerja;
        $pria = $request->pria;
        $wanita = $request->wanita;
        $kode_site = $request->kode_site;
        $kode_plot = $request->kode_plot;
        $lat_detail_planting_action = $request->coordinate["latitude"];
        $long_detail_planting_action = $request->coordinate["longitude"];
        $sistem_tanam_1 = $request->sistem_tanam_1;
        $sistem_tanam_2 = $request->sistem_tanam_2;
        $r_mucronota = $request->r_mucronota;
        $r_stylosa = $request->r_stylosa;
        $r_apiculata = $request->r_apiculata;
        $avicennia_spp = $request->avicennia_spp;
        $ceriops_spp = $request->ceriops_spp;
        $xylocarpus_spp = $request->xylocarpus_spp;
        $bruguiera_spp = $request->bruguiera_spp;
        $sonneratia_spp = $request->sonneratia_spp;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $kindPlantingAction = DB::insert(" INSERT INTO yakopi_detail_planting_action (id_detail_planting_action,id_planting_action,date_planting_action,jumlah_pekerja,pria,wanita,kode_site,kode_plot,lat_detail_planting_action,long_detail_planting_action,sistem_tanam_1,sistem_tanam_2,r_mucronota,r_stylosa,r_apiculata,avicennia_spp,ceriops_spp,xylocarpus_spp,bruguiera_spp,sonneratia_spp,created_by,created_time)
        VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ",[$id_planting_action,$date_planting_action,$jumlah_pekerja,$pria,$wanita,$kode_site,$kode_plot,$lat_detail_planting_action,$long_detail_planting_action,$sistem_tanam_1,$sistem_tanam_2,$r_mucronota,$r_stylosa,$r_apiculata,$avicennia_spp,$ceriops_spp,$xylocarpus_spp,$bruguiera_spp,$sonneratia_spp,$created_by,$created_time]);
        if($kindPlantingAction){
            return [
                "success"=>true,
                "msg"=>"Data berhasil ditambahkan"
            ];
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat ditambahkan"
            ];
        }
    });

    Route::delete("/delete-kind-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_detail_planting_action = $request->id_detail_planting_action;
        
        $cekId = DB::select("SELECT * FROM yakopi_detail_planting_action WHERE id_detail_planting_action=?",[$id_detail_planting_action]);
        if(count($cekId)>0){
            $id_planting_action = $cekId[0]->id_planting_action;

            $cekStatus = DB::select("SELECT * FROM yakopi_planting_action WHERE id_planting_action=?",[$id_planting_action]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_detail_planting_action WHERE id_detail_planting_action=?",[$id_detail_planting_action]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/photo-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_planting_action;

        $photo = DB::select("SELECT * FROM yakopi_planting_action_photo WHERE id_detail_planting_action=?",[$id]);

        return [
            "success"=>true,
            "data"=>$photo
        ];
    });

    Route::post("/add-photo-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_planting_action;
        $keterangan = $request->keterangan_planting_action_photo;
        $link = $request->link_planting_action_photo;
        $file = $request->file_planting_action_photo;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $photo = DB::insert("INSERT INTO yakopi_planting_action_photo (id_planting_action_photo,id_detail_planting_action,keterangan_planting_action_photo,link_planting_action_photo,file_planting_action_photo,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-photo-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_planting_action_photo;

        $cekId = DB::select("SELECT * FROM yakopi_planting_action_photo WHERE id_planting_action_photo=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_detail_planting_action;

            $cekStatus1 = DB::select("SELECT * FROM yakopi_detail_planting_action WHERE id_detail_planting_action=?",[$id1]);

            $id2 = $cekStatus1[0]->id_planting_action;

            $cekStatus = DB::select("SELECT * FROM yakopi_planting_action WHERE id_planting_action=?",[$id2]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_planting_action_photo WHERE id_planting_action_photo=?",[$id]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/video-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_planting_action;

        $video = DB::select("SELECT * FROM yakopi_planting_action_video WHERE id_detail_planting_action=?",[$id]);

        return [
            "success"=>true,
            "data"=>$video
        ];
    });

    Route::post("/add-video-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_planting_action;
        $keterangan = $request->keterangan_planting_action_video;
        $link = $request->link_planting_action_video;
        $file = $request->file_planting_action_video;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $video = DB::insert("INSERT INTO yakopi_planting_action_video (id_planting_action_video,id_detail_planting_action,keterangan_planting_action_video,link_planting_action_video,file_planting_action_video,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-video-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_planting_action_video;

        $cekId = DB::select("SELECT * FROM yakopi_planting_action_video WHERE id_planting_action_video=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_detail_planting_action;

            $cekStatus1 = DB::select("SELECT * FROM yakopi_detail_planting_action WHERE id_detail_planting_action=?",[$id1]);

            $id2 = $cekStatus1[0]->id_planting_action;

            $cekStatus = DB::select("SELECT * FROM yakopi_planting_action WHERE id_planting_action=?",[$id2]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_planting_action_video WHERE id_planting_action_video=?",[$id]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/drone-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_planting_action;

        $drone = DB::select("SELECT * FROM yakopi_planting_action_drone WHERE id_detail_planting_action=?",[$id]);
        
        return [
            "success"=>true,
            "data"=>$drone
        ];

    });

    Route::post("/add-drone-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_planting_action;
        $keterangan = $request->keterangan_planting_action_drone;
        $link = $request->link_planting_action_drone;
        $file = $request->file_planting_action_drone;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $drone = DB::insert("INSERT INTO yakopi_planting_action_drone (id_planting_action_drone,id_detail_planting_action,keterangan_planting_action_drone,link_planting_action_drone,file_planting_action_drone,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-drone-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_planting_action_drone;

        $cekId = DB::select("SELECT * FROM yakopi_planting_action_drone WHERE id_planting_action_drone=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_detail_planting_action;

            $cekStatus1 = DB::select("SELECT * FROM yakopi_detail_planting_action WHERE id_detail_planting_action=?",[$id1]);

            $id2 = $cekStatus1[0]->id_planting_action;

            $cekStatus = DB::select("SELECT * FROM yakopi_planting_action WHERE id_planting_action=?",[$id2]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_planting_action_drone WHERE id_planting_action_drone=?",[$id]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::delete("/delete-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_planting_action;

        $cekId = DB::select("SELECT * FROM yakopi_planting_action WHERE id_planting_action=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_planting_action;

            $cekStatus = DB::select("SELECT * FROM yakopi_planting_action WHERE id_planting_action=?",[$id1]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_planting_action WHERE id_planting_action=?",[$id1]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    // PLANTING ACTION END

    // TRANSPORT START
    
    Route::get("/transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $transport = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_transport AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");

        return [
            "success"=>true,
            "data"=>$transport
        ];

    });

    Route::get("/transport/{id_transport}", function (Request $request,$id_transport){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $transport = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_transport AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_transport=?
        ",[$id_transport]);

        $detail_transport = DB::select("
        SELECT * FROM yakopi_detail_transport WHERE id_transport=?",[$id_transport]);

        return [
            "success"=>true,
            "data"=>$transport,
            "detail_transport"=>$detail_transport
        ];

    });

    Route::get("/history-transport/{id_pengguna}", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $transport = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_transport AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.created_by=?
        ",[$json->id_pengguna]);

        return [
            "success"=>true,
            "data"=>$transport
        ];
    });

    Route::post("/transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $lat_transport = $request->coordinate["latitude"];
        $long_transport = $request->coordinate["longitude"];
        $transport_info = $request->transport_info;
        $daerah_tujuan = $request->daerah_tujuan;
        $kecamatan = $request->kecamatan;
        $desa = $request->desa;
        $dusun = $request->dusun;
        $catatan_1 = $request->catatan_1;
        $catatan_2 = $request->catatan_2;
        $dilaporkan_oleh = $request->dilaporkan_oleh;
        $ttd_pelapor = "";
        $created_by = $json->id_pengguna;  
        $created_time = date("Y-m-d H:i:s");
        $status = 0;

        $transport = DB::insert(" INSERT INTO yakopi_transport (id_transport,id_project,id_provinces,id_cities,id_districts,nama_desa,nama_dusun,lat_transport,long_transport,transport_info,daerah_tujuan,kecamatan,desa,dusun,catatan_1,catatan_2,dilaporkan_oleh,ttd_pelapor,created_by,created_time,status)
        VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ",
        [$id_project,$id_provinces,$id_cities,$id_districts,$nama_desa,$nama_dusun,$lat_transport,$long_transport,$transport_info,$daerah_tujuan,$kecamatan,$desa,$dusun,$catatan_1,$catatan_2,$dilaporkan_oleh,$ttd_pelapor,$created_by,$created_time,$status]);
    
        if ($transport) {
            return [
                "success"=>true,
                "message"=>"Transport berhasil ditambahkan"
            ];
        }else{
            return [
                "success"=>false,
                "message"=>"Transport gagal ditambahkan"
            ];
        }
    });

    Route::post("/approve-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_transport = $request->id_transport;
        $status = 1;

        $approve = DB::update("UPDATE yakopi_transport SET status=? WHERE id_transport=?",[$status,$id_transport]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Mengkonfirmasi Data"
        ];
    });

    Route::post("/reject-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_transport = $request->id_transport;
        $status = 2;

        $reject = DB::update("UPDATE yakopi_transport SET status=? WHERE id_transport=?",[$status,$id_transport]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Menolak Data"
        ];
    });
    
    Route::post("/kind-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_transport = $request->id_transport;

        $kind_planting_action = DB::select("SELECT * FROM yakopi_detail_transport WHERE id_transport=?",[$id_transport]);

        return [
            "success"=>true,
            "data"=>$kind_planting_action
        ];
    });

    Route::post("/add-kind-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_transport = $request->id_transport;
        $date_transport = $request->date_transport;
        $jumlah_pekerja = $request->jumlah_pekerja;
        $pria = $request->pria;
        $wanita = $request->wanita;
        $lat_detail_transport = $request->coordinate["latitude"];
        $long_detail_transport = $request->coordinate["longitude"];
        $r_mucronota = $request->r_mucronota;
        $r_stylosa = $request->r_stylosa;
        $r_apiculata = $request->r_apiculata;
        $avicennia_spp = $request->avicennia_spp;
        $ceriops_spp = $request->ceriops_spp;
        $xylocarpus_spp = $request->xylocarpus_spp;
        $bruguiera_spp = $request->bruguiera_spp;
        $sonneratia_spp = $request->sonneratia_spp;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $kindTransport = DB::insert("INSERT INTO yakopi_detail_transport (id_detail_transport,id_transport,date_transport,jumlah_pekerja,pria,wanita,lat_detail_transport,long_detail_transport,r_mucronota,r_stylosa,r_apiculata,avicennia_spp,ceriops_spp,xylocarpus_spp,bruguiera_spp,sonneratia_spp,created_by,created_time)
        VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        ,[$id_transport,$date_transport,$jumlah_pekerja,$pria,$wanita,$lat_detail_transport,$long_detail_transport,$r_mucronota,$r_stylosa,$r_apiculata,$avicennia_spp,$ceriops_spp,$xylocarpus_spp,$bruguiera_spp,$sonneratia_spp,$created_by,$created_time]);
        
        if ($kindTransport) {
            return [
                "success"=>true,
                "msg"=>"Data berhasil disimpan"
            ];
        }else{
            return [
                "success"=>false,
                "msg"=>"Data gagal disimpan"
            ];
        }
    });

    Route::delete("/delete-kind-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_detail_transport = $request->id_detail_transport;
        
        $cekId = DB::select("SELECT * FROM yakopi_detail_transport WHERE id_detail_transport=?",[$id_detail_transport]);
        if(count($cekId)>0){
            $id_transport = $cekId[0]->id_transport;

            $cekStatus = DB::select("SELECT * FROM yakopi_transport WHERE id_transport=?",[$id_transport]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_detail_transport WHERE id_detail_transport=?",[$id_detail_transport]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/photo-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_transport;

        $photo = DB::select("SELECT * FROM yakopi_transport_photo WHERE id_detail_transport=?",[$id]);

        return [
            "success"=>true,
            "data"=>$photo
        ];
    });

    Route::post("/add-photo-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_transport;
        $keterangan = $request->keterangan_transport_photo;
        $link = $request->link_transport_photo;
        $file = $request->file_transport_photo;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $photo = DB::insert("INSERT INTO yakopi_transport_photo (id_transport_photo,id_detail_transport,keterangan_transport_photo,link_transport_photo,file_transport_photo,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-photo-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_transport_photo;

        $cekId = DB::select("SELECT * FROM yakopi_transport_photo WHERE id_transport_photo=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_detail_transport;

            $cekStatus1 = DB::select("SELECT * FROM yakopi_detail_transport WHERE id_detail_transport=?",[$id1]);

            $id2 = $cekStatus1[0]->id_transport;

            $cekStatus = DB::select("SELECT * FROM yakopi_transport WHERE id_transport=?",[$id2]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_transport_photo WHERE id_transport_photo=?",[$id]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/video-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_transport;

        $video = DB::select("SELECT * FROM yakopi_transport_video WHERE id_detail_transport=?",[$id]);

        return [
            "success"=>true,
            "data"=>$video
        ];
    });

    Route::post("/add-video-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_transport;
        $keterangan = $request->keterangan_transport_video;
        $link = $request->link_transport_video;
        $file = $request->file_transport_video;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $video = DB::insert("INSERT INTO yakopi_transport_video (id_transport_video,id_detail_transport,keterangan_transport_video,link_transport_video,file_transport_video,created_by,created_time)
        VALUES (null,?,?,?,?,?,?)"
        ,[$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-video-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_transport_video;

        $cekId = DB::select("SELECT * FROM yakopi_transport_video WHERE id_transport_video=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_detail_transport;

            $cekStatus1 = DB::select("SELECT * FROM yakopi_detail_transport WHERE id_detail_transport=?",[$id1]);

            $id2 = $cekStatus1[0]->id_transport;

            $cekStatus = DB::select("SELECT * FROM yakopi_transport WHERE id_transport=?",[$id2]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_transport_video WHERE id_transport_video=?",[$id]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/drone-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_transport;

        $drone = DB::select("SELECT * FROM yakopi_transport_drone WHERE id_detail_transport=?",[$id]);
        
        return [
            "success"=>true,
            "data"=>$drone
        ];

    });

    Route::post("/add-drone-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_transport;
        $keterangan = $request->keterangan_transport_drone;
        $link = $request->link_transport_drone;
        $file = $request->file_transport_drone;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $drone = DB::insert("INSERT INTO yakopi_transport_drone (id_transport_drone,id_detail_transport,keterangan_transport_drone,link_transport_drone,file_transport_drone,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-drone-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_transport_drone;

        $cekId = DB::select("SELECT * FROM yakopi_transport_drone WHERE id_transport_drone=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_detail_transport;

            $cekStatus1 = DB::select("SELECT * FROM yakopi_detail_transport WHERE id_detail_transport=?",[$id1]);

            $id2 = $cekStatus1[0]->id_transport;

            $cekStatus = DB::select("SELECT * FROM yakopi_transport WHERE id_transport=?",[$id2]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_transport_drone WHERE id_transport_drone=?",[$id]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::delete("/delete-transport", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_transport;

        $cekId = DB::select("SELECT * FROM yakopi_transport WHERE id_transport=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_transport;

            $cekStatus = DB::select("SELECT * FROM yakopi_transport WHERE id_transport=?",[$id1]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_transport WHERE id_transport=?",[$id1]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    // TRANSPORT END

    // GROWTH START

    Route::get("/growth", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $data = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_growth AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");

        return [
            "success"=>true,
            "data"=>$data
        ];

    });

    Route::get("/growth/{id_growth}", function (Request $request,$id_growth){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $data = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_growth AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_growth=?
        ",[$id_growth]);

        $detail = DB::select("
        SELECT * FROM yakopi_detail_growth WHERE id_growth=?",[$id_growth]);

        return [
            "success"=>true,
            "data"=>$data,
            "detail"=>$detail
        ];

    });

    Route::get("/history-growth/{id_pengguna}", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $data = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_growth AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.created_by=?
        ",[$json->id_pengguna]);

        return [
            "success"=>true,
            "data"=>$data
        ];
    });

    Route::post("/growth", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $posisi_site = $request->position;
        $distance_growth = $request->distance;
        $type_magrove_growth = $request->type_magrove;
        $catatan_khusus_1 = $request->catatan_khusus_1;
        $catatan_khusus_2 = $request->catatan_khusus_2;
        $nama_surveyor = $request->nama_surveyor;
        $ttd_surveyor = "";
        $created_by = $json->id_pengguna;  
        $created_time = date("Y-m-d H:i:s");
        $status = 0;

        $data = DB::insert(" INSERT INTO yakopi_growth (id_growth,id_project,id_provinces,id_cities,id_districts,nama_desa,nama_dusun,posisi_site,distance_growth,type_magrove_growth,catatan_khusus_1,catatan_khusus_2,nama_surveyor,ttd_surveyor,created_by,created_time,status) 
        VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
        [$id_project,$id_provinces,$id_cities,$id_districts,$nama_desa,$nama_dusun,$posisi_site,$distance_growth,$type_magrove_growth,$catatan_khusus_1,$catatan_khusus_2,$nama_surveyor,$ttd_surveyor,$created_by,$created_time,$status]);
        
        if ($data) {
            return [
                "success"=>true,
                "message"=>"Growth berhasil ditambahkan"
            ];
        }else{
            return [
                "success"=>false,
                "message"=>"Growth gagal ditambahkan"
            ];
        }
    });

    Route::post("/approve-growth", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_growth = $request->id_growth;
        $status = 1;

        $approve = DB::update("UPDATE yakopi_growth SET status=? WHERE id_growth=?",[$status,$id_growth]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Mengkonfirmasi Data"
        ];
    });

    Route::post("/reject-growth", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_growth = $request->id_growth;
        $status = 2;

        $reject = DB::update("UPDATE yakopi_growth SET status=? WHERE id_growth=?",[$status,$id_growth]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Menolak Data"
        ];
    });

    Route::post("/kind-growth", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_growth = $request->id_growth;

        $kind = DB::select("SELECT * FROM yakopi_detail_growth WHERE id_growth=?",[$id_growth]);

        return [
            "success"=>true,
            "data"=>$kind
        ];
    });

    Route::post("/add-kind-growth", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_growth = $request->id_growth;
        $site_code = $request->site_code;
        $plot_code = $request->plot_code;
        $luas = $request->luas;
        $lat_detail_growth = $request->coordinate["latitude"];
        $long_detail_growth = $request->coordinate["longitude"];
        $jenis_magrove = $request->jenis_magrove;
        $kematian = $request->kematian;
        $penyebab_kematian = $request->penyebab_kematian;
        $jenis_tanah = $request->jenis_tanah;
        $status_tambak = $request->status_tambak;
        $biodiversity = $request->biodiversity;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $kind = DB::insert("INSERT INTO yakopi_detail_growth (id_detail_growth,id_growth,site_code,plot_code,luas,lat_detail_growth,long_detail_growth,jenis_magrove,kematian,penyebab_kematian,jenis_tanah,status_tambak,biodiversity,created_by,created_time)
        VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
        [$id_growth,$site_code,$plot_code,$luas,$lat_detail_growth,$long_detail_growth,$jenis_magrove,$kematian,$penyebab_kematian,$jenis_tanah,$status_tambak,$biodiversity,$created_by,$created_time]);
        
        if ($kind) {
            return [
                "success"=>true,
                "msg"=>"Data berhasil disimpan"
            ];
        }else{
            return [
                "success"=>false,
                "msg"=>"Data gagal disimpan"
            ];
        }
    });

    Route::delete("/delete-kind-growth", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_detail_growth = $request->id_detail_growth;
        
        $cekId = DB::select("SELECT * FROM yakopi_detail_growth WHERE id_detail_growth=?",[$id_detail_growth]);
        if(count($cekId)>0){
            $id_growth = $cekId[0]->id_growth;

            $cekStatus = DB::select("SELECT * FROM yakopi_growth WHERE id_growth=?",[$id_growth]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_detail_growth WHERE id_detail_growth=?",[$id_detail_growth]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::delete("/delete-growth", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_growth;

        $cekId = DB::select("SELECT * FROM yakopi_growth WHERE id_growth=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_growth;

            $cekStatus = DB::select("SELECT * FROM yakopi_growth WHERE id_growth=?",[$id1]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_growth WHERE id_growth=?",[$id1]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    // END GROWTH

    // REPLANTING ACTION START

    Route::get("/replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_planting_action;

        $planting = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_replanting AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");

        return [
            "success"=>true,
            "data"=>$planting
        ];

    });

    Route::get("/replanting/{id_replanting}", function (Request $request,$id_replanting){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $plantingAction = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_replanting AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_replanting=?
        ",[$id_replanting]);

        $detailPlantingAction = DB::select("
        SELECT * FROM yakopi_detail_replanting WHERE id_replanting=?",[$id_replanting]);

        return [
            "success"=>true,
            "data"=>$plantingAction,
            "detail_planting_action"=>$detailPlantingAction
        ];

    });

    Route::get("/history-replanting/{id_pengguna}", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $plantingAction = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_replanting AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.created_by=?
        ",[$json->id_pengguna]);

        return [
            "success"=>true,
            "data"=>$plantingAction
        ];
    });

    Route::post("/replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $jarak_tanam = $request->jarak_tanam;
        $lokasi_tanam = $request->lokasi_tanam;
        $info_1 = $request->info_1;
        $catatan_1 = $request->catatan_1;
        $catatan_2 = $request->catatan_2;
        $dilaporkan_oleh = $request->dilaporkan_oleh;
        $ttd_pelapor = "";
        $created_by = $json->id_pengguna;  
        $created_time = date("Y-m-d H:i:s");
        $status = 0;

        $plantingAction = DB::insert(" INSERT INTO yakopi_replanting (id_planting_action,id_project,id_provinces,id_cities,id_districts,nama_desa,nama_dusun,jarak_tanam,lokasi_tanam,info_1,catatan_1,catatan_2,dilaporkan_oleh,ttd_pelapor,created_by,created_time,status)
        VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ",[$id_project,$id_provinces,$id_cities,$id_districts,$nama_desa,$nama_dusun,$jarak_tanam,$lokasi_tanam,$info_1,$catatan_1,$catatan_2,$dilaporkan_oleh,$ttd_pelapor,$created_by,$created_time,$status]);
        if($plantingAction){
            return [
                "success"=>true,
                "msg"=>"Data berhasil ditambahkan"
            ];
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat ditambahkan"
            ];
        }
    });

    Route::post("/approve-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_replanting = $request->id_replanting;
        $status = 1;

        $approvePlantingAction = DB::update("UPDATE yakopi_replanting SET status=? WHERE id_replanting=?",[$status,$id_replanting]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Mengkonfirmasi Data"
        ];
    });

    Route::post("/reject-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_replanting = $request->id_replanting;
        $status = 2;

        $rejectPlantingAction = DB::update("UPDATE yakopi_replanting SET status=? WHERE id_replanting=?",[$status,$id_replanting]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Menolak Data"
        ];
    });

    Route::post("/kind-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_replanting = $request->id_replanting;

        $kind_planting_action = DB::select("SELECT * FROM yakopi_detail_replanting WHERE id_replanting=?",[$id_replanting]);

        return [
            "success"=>true,
            "data"=>$kind_planting_action
        ];
    });

    Route::post("/add-kind-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_replanting = $request->id_replanting;
        $site_code = $request->site_code;
        $plot_code = $request->plot_code;
        $area_plot = $request->area_plot;
        $lat_detail_replanting = $request->coordinate["latitude"];
        $long_detail_replanting = $request->coordinate["longitude"];
        $kematian = $request->kematian;
        $sistem_tanam_1 = $request->sistem_tanam_1;
        $sistem_tanam_2 = $request->sistem_tanam_2;
        $r_mucronota = $request->r_mucronota;
        $r_stylosa = $request->r_stylosa;
        $r_apiculata = $request->r_apiculata;
        $avicennia_spp = $request->avicennia_spp;
        $ceriops_spp = $request->ceriops_spp;
        $xylocarpus_spp = $request->xylocarpus_spp;
        $bruguiera_spp = $request->bruguiera_spp;
        $sonneratia_spp = $request->sonneratia_spp;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $kindPlantingAction = DB::insert(" INSERT INTO yakopi_detail_replanting (id_detail_replanting,id_replanting,site_code,plot_code,area_plot,lat_detail_replanting,long_detail_replanting,kematian,sistem_tanam_1,sistem_tanam_2,r_mucronota,r_stylosa,r_apiculata,avicennia_spp,ceriops_spp,xylocarpus_spp,bruguiera_spp,sonneratia_spp,created_by,created_time)
        VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ",[$id_replanting,$site_code,$plot_code,$area_plot,$lat_detail_replanting,$long_detail_replanting,$kematian,$sistem_tanam_1,$sistem_tanam_2,$r_mucronota,$r_stylosa,$r_apiculata,$avicennia_spp,$ceriops_spp,$xylocarpus_spp,$bruguiera_spp,$sonneratia_spp,$created_by,$created_time]);
        if($kindPlantingAction){
            return [
                "success"=>true,
                "msg"=>"Data berhasil ditambahkan"
            ];
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat ditambahkan"
            ];
        }
    });

    Route::delete("/delete-kind-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_detail_replanting = $request->id_detail_replanting;
        
        $cekId = DB::select("SELECT * FROM yakopi_detail_replanting WHERE id_detail_replanting=?",[$id_detail_replanting]);
        if(count($cekId)>0){
            $id_replanting = $cekId[0]->id_replanting;

            $cekStatus = DB::select("SELECT * FROM yakopi_replanting WHERE id_replanting=?",[$id_replanting]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_detail_replanting WHERE id_detail_replanting=?",[$id_detail_replanting]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/photo-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_replanting;

        $photo = DB::select("SELECT * FROM yakopi_replanting_photo WHERE id_detail_replanting=?",[$id]);

        return [
            "success"=>true,
            "data"=>$photo
        ];
    });

    Route::post("/add-photo-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_replanting;
        $keterangan = $request->keterangan_replanting_photo;
        $link = $request->link_replanting_photo;
        $file = $request->file_replanting_photo;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $photo = DB::insert("INSERT INTO yakopi_replanting_photo (id_replanting_photo,id_detail_replanting,keterangan_replanting_photo,link_replanting_photo,file_replanting_photo,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-photo-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_replanting_photo;

        $cekId = DB::select("SELECT * FROM yakopi_replanting_photo WHERE id_replanting_photo=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_detail_replanting;

            $cekStatus1 = DB::select("SELECT * FROM yakopi_detail_replanting WHERE id_detail_replanting=?",[$id1]);

            $id2 = $cekStatus1[0]->id_replanting;

            $cekStatus = DB::select("SELECT * FROM yakopi_replanting WHERE id_replanting=?",[$id2]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_replanting_photo WHERE id_replanting_photo=?",[$id]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/video-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_replanting;

        $video = DB::select("SELECT * FROM yakopi_replanting_video WHERE id_detail_replanting=?",[$id]);

        return [
            "success"=>true,
            "data"=>$video
        ];
    });

    Route::post("/add-video-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_replanting;
        $keterangan = $request->keterangan_replanting_video;
        $link = $request->link_replanting_video;
        $file = $request->file_replanting_video;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $video = DB::insert("INSERT INTO yakopi_replanting_video (id_replanting_video,id_detail_replanting,keterangan_replanting_video,link_replanting_video,file_replanting_video,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-video-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_replanting_video;

        $cekId = DB::select("SELECT * FROM yakopi_replanting_video WHERE id_replanting_video=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_detail_replanting;

            $cekStatus1 = DB::select("SELECT * FROM yakopi_detail_replanting WHERE id_detail_replanting=?",[$id1]);

            $id2 = $cekStatus1[0]->id_replanting;

            $cekStatus = DB::select("SELECT * FROM yakopi_replanting WHERE id_replanting=?",[$id2]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_replanting_video WHERE id_replanting_video=?",[$id]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/drone-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_replanting;

        $drone = DB::select("SELECT * FROM yakopi_replanting_drone WHERE id_detail_replanting=?",[$id]);
        
        return [
            "success"=>true,
            "data"=>$drone
        ];

    });

    Route::post("/add-drone-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_detail_replanting;
        $keterangan = $request->keterangan_replanting_drone;
        $link = $request->link_replanting_drone;
        $file = $request->file_replanting_drone;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $drone = DB::insert("INSERT INTO yakopi_replanting_drone (id_replanting_drone,id_detail_replanting,keterangan_replanting_drone,link_replanting_drone,file_replanting_drone,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-drone-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_replanting_drone;

        $cekId = DB::select("SELECT * FROM yakopi_replanting_drone WHERE id_replanting_drone=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_detail_replanting;

            $cekStatus1 = DB::select("SELECT * FROM yakopi_detail_replanting WHERE id_detail_replanting=?",[$id1]);

            $id2 = $cekStatus1[0]->id_replanting;

            $cekStatus = DB::select("SELECT * FROM yakopi_replanting WHERE id_replanting=?",[$id2]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_replanting_drone WHERE id_replanting_drone=?",[$id]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::delete("/delete-replanting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_replanting;

        $cekId = DB::select("SELECT * FROM yakopi_replanting WHERE id_replanting=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_replanting;

            $cekStatus = DB::select("SELECT * FROM yakopi_replanting WHERE id_replanting=?",[$id1]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_replanting WHERE id_replanting=?",[$id1]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    // REPLANTING ACTION END



        


    // COMMUNITY DEVELOPMENT START

    Route::get("/community-register", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $community_development = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_community_register AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");
    
        return [
            "success"=>true,
            "data"=>$community_development
        ];
    });

    Route::get("/community-register/{id}", function(Request $request, $id){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $community_development = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_community_register AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_community_register=?
        ",[$id]);

        return [
            "success"=>true,
            "data"=>$community_development[0]
        ];
    });
    
    Route::post("/community-register", function(Request $request){
        $token = $request->bearerToken();
    
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $nomor_mou = $request->nomor_mou;
        $nama_kelompok = $request->nama_kelompok;
        $ketua_kelompok = $request->ketua_kelompok;
        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $jumlah_site = $request->jumlah_site;
        $jumlah_plot = $request->jumlah_plot;
        $luas_area_mou = $request->luas_area_mou;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");
        $status = "0";

        $insertcommunity = DB::insert("INSERT INTO yakopi_community_register 
        VALUES (NULL,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        ,[$nomor_mou,$nama_kelompok,$ketua_kelompok,$id_project,$id_provinces,$id_cities,$id_districts,$nama_desa,$nama_dusun,$jumlah_site,$jumlah_plot,$luas_area_mou,$created_by,$created_time,$status]);
    
        return [
            "success"=>true,
            "msg"=>"Berhasil melakukan pendaftaran"
        ];
    });

    Route::delete("/delete-community-register", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_community_register;

        $cekIdCommunity = DB::select("SELECT * FROM yakopi_community_register WHERE id_community_register=?",[$id]);

        if(count($cekIdCommunity)>0){
            $id_community_register = $cekIdCommunity[0]->id_community_register;

            $cekStatus = DB::select("SELECT * FROM yakopi_community_register WHERE id_community_register=?",[$id_community_register]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_community_register WHERE id_community_register=?",[$id_community_register]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/approve-community-register", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_community_register;

        $cekIdCommunity = DB::select("SELECT * FROM yakopi_community_register WHERE id_community_register=?",[$id]);

        if(count($cekIdCommunity)>0){
            $id_community_register = $cekIdCommunity[0]->id_community_register;

            $cekStatus = DB::select("SELECT * FROM yakopi_community_register WHERE id_community_register=?",[$id_community_register]);

            if($cekStatus[0]->status=="0"){

                $update = DB::update("UPDATE yakopi_community_register SET status='1' WHERE id_community_register=?",[$id_community_register]);

                return [
                    "success"=>true,
                    "msg"=>"Data berhasil diperbarui"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat diperbarui"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat diperbarui"
            ];
        }
    });

    Route::post("/reject-community-register", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_community_register;

        $cekIdCommunity = DB::select("SELECT * FROM yakopi_community_register WHERE id_community_register=?",[$id]);

        if(count($cekIdCommunity)>0){
            $id_community_register = $cekIdCommunity[0]->id_community_register;

            $cekStatus = DB::select("SELECT * FROM yakopi_community_register WHERE id_community_register=?",[$id_community_register]);

            if($cekStatus[0]->status=="0"){

                $update = DB::update("UPDATE yakopi_community_register SET status='2' WHERE id_community_register=?",[$id_community_register]);

                return [
                    "success"=>true,
                    "msg"=>"Data berhasil diperbarui"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat diperbarui"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat diperbarui"
            ];
        }

    });
    
    Route::get("/silvoshery", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $silvoshery = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_silvoshery AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");
    
        return [
            "success"=>true,
            "data"=>$silvoshery
        ];
    });

    Route::get("/silvoshery/{id}", function(Request $request, $id){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $silvoshery = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_silvoshery AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_silvoshery=?
        ",[$id]);
    
        return [
            "success"=>true,
            "data"=>$silvoshery[0]
        ];
    });
    
    Route::post("/silvoshery", function(Request $request){
        $token = $request->bearerToken();
    
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $kode_silvoshery = $request->kode_silvoshery;
        $pemilik_tambak = $request->pemilik_tambak;
        $jumlah_tanaman = $request->jumlah_tanaman;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");
        $status = "0";
    
        $insertsilvoshery = DB::insert("INSERT INTO yakopi_silvoshery VALUES (NULL,?,?,?,?,?,?,?,?,?,?,?,?)",[$id_project,$id_provinces,$id_cities,$id_districts,$nama_desa,$nama_dusun,$kode_silvoshery,$pemilik_tambak,$jumlah_tanaman,$created_by,$created_time,$status]);
        return [
            "success"=>true,
            "msg"=>"Berhasil melakukan pendaftaran"
        ];
    });

    Route::delete("/delete-silvoshery", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_silvoshery;

        $cekIdSilvoshery = DB::select("SELECT * FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id]);

        if(count($cekIdSilvoshery)>0){
            $id_silvoshery = $cekIdSilvoshery[0]->id_silvoshery;

            $cekStatus = DB::select("SELECT * FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id_silvoshery]);

            if($cekStatus[0]->status=="0"){
                
                $delete = DB::delete("DELETE FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id_silvoshery]);

                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];

            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }

    });

    Route::post("/approve-silvoshery", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_silvoshery;

        $cekIdSilvoshery = DB::select("SELECT * FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id]);

        if(count($cekIdSilvoshery)>0){
            $id_silvoshery = $cekIdSilvoshery[0]->id_silvoshery;

            $cekStatus = DB::select("SELECT * FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id_silvoshery]);

            if($cekStatus[0]->status=="0"){

                $update = DB::update("UPDATE yakopi_silvoshery SET status='1' WHERE id_silvoshery=?",[$id_silvoshery]);

                return [
                    "success"=>true,
                    "msg"=>"Data berhasil diperbarui"
                ];

            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat diperbarui"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat diperbarui"
            ];
        }

    });

    Route::post("/reject-silvoshery", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_silvoshery;

        $cekIdSilvoshery = DB::select("SELECT * FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id]);

        if(count($cekIdSilvoshery)>0){
            $id_silvoshery = $cekIdSilvoshery[0]->id_silvoshery;

            $cekStatus = DB::select("SELECT * FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id_silvoshery]);

            if($cekStatus[0]->status=="0"){

                $update = DB::update("UPDATE yakopi_silvoshery SET status='2' WHERE id_silvoshery=?",[$id_silvoshery]);

                return [
                    "success"=>true,
                    "msg"=>"Data berhasil diperbarui"
                ];

            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat diperbarui"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat diperbarui"
            ];
        }
    });

    // COMMUNITY DEVELOPMENT END

    // RESEARCH START

    // GROWTH RESEARCH START

    Route::get("/research/growthResearch", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
        
        $data = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_growth_research AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");

        return [
            "success"=>true,
            "data"=>$data
        ];
    });

    Route::get("/research/growthResearch/{id}", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id;

        $data = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_growth_research AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_growth_research=?",[$id]);

        $detail = DB::select("SELECT * FROM yakopi_detail_growth_research WHERE id_growth_research=?",[$data[0]->id_growth_research]);

        return [
            "success"=>true,
            "data"=>$data,
            "detail"=>$detail
        ];

    });

    Route::post("/research/growthResearch/add", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $lat_growth_research = $request->coordinate["latitude"];
        $long_growth_research = $request->coordinate["longitude"];
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $site_code = $request->site_code;
        $plot_code = $request->plot_code;
        $area = $request->area;
        $spesies = $request->species;
        $jumlah = $request->jumlah;
        $monitoring_ke = $request->monitoring_ke;
        $catatan_1 = $request->catatan_1;
        $catatan_2 = $request->catatan_2;
        $dilaporkan_oleh = $request->dilaporkan_oleh;
        $ttd_pelapor = '';
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");
        $status = "0";

        $insert = DB::insert(" INSERT INTO yakopi_growth_research 
        (id_growth_research,id_project,id_provinces,id_cities,id_districts,lat_growth_research,long_growth_research,nama_desa,nama_dusun,site_code,plot_code,area,spesies,jumlah,monitoring_ke,catatan_1,catatan_2,dilaporkan_oleh,ttd_pelapor,created_by,created_time,status) 
        VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        ,[$id_project,$id_provinces,$id_cities,$id_districts,$lat_growth_research,$long_growth_research,$nama_desa,$nama_dusun,$site_code,$plot_code,$area,$spesies,$jumlah,$monitoring_ke,$catatan_1,$catatan_2,$dilaporkan_oleh,$ttd_pelapor,$created_by,$created_time,$status]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil ditambahkan"
        ];

    });

    Route::delete("/research/growthResearch/delete/{id}", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id;

        $data = DB::select("SELECT * FROM yakopi_growth_research WHERE id_growth_research=?",[$id]);
        
        if($data[0]->status=="0"){
            $delete = DB::delete("DELETE FROM yakopi_growth_research WHERE id_growth_research=?",[$id]);

            if($delete){
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }

    });

    Route::get("/research/dataGrowthResearch/{id}", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id;

        $data = DB::select(" SELECT * FROM yakopi_detail_growth_research WHERE id_growth_research=?",[$id]);

        return [
            "success"=>true,
            "data"=>$data
        ];

    });

    Route::post("/research/growthResearch/addDetail", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_growth_research = $request->id_growth_research;
        $no_tagging = $request->no_tagging;
        $tinggi = $request->tinggi;
        $diameter = $request->diameter;
        $jumlah_daun = $request->jumlah_daun;
        $jumlah_percabangan = $request->jumlah_percabangan;
        $keterangan = $request->keterangan;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $insert = DB::insert(" INSERT INTO yakopi_detail_growth_research
        (id_detail_growth_research,id_growth_research,no_tagging,tinggi,diameter,jumlah_daun,jumlah_percabangan,keterangan,created_by,created_time)
        VALUES (null,?,?,?,?,?,?,?,?,?,?)"
        ,[$id_growth_research,$no_tagging,$tinggi,$diameter,$jumlah_daun,$jumlah_percabangan,$keterangan,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil ditambahkan"
        ];
    });

    Route::delete("/research/growthResearch/deleteDetail/{id}", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id;

        $cek = DB::select("SELECT * FROM yakopi_detail_growth_research WHERE id_detail_growth_research=?",[$id]);

        if(count($cek)>0){
            $cekStatus = DB::select("SELECT * FROM yakopi_growth_research WHERE id_growth_research=?",[$cek[0]->id_growth_research]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_detail_growth_research WHERE id_detail_growth_research=?",[$id]);

                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus karena sudah dilaporkan"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak ditemukan"
            ];
        }
    });

    Route::get("/research/photoDataGrowthResearch/{id}", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id;

        $data = DB::select("SELECT * FROM yakopi_photo_growth_research WHERE id_detail_growth_research=?",[$id]);

        return [
            "success"=>true,
            "data"=>$data
        ];

    });

    Route::post("/research/growthResearch/addPhoto", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_detail_growth_research = $request->id_detail_growth_research;
        $link_growth_research_photo = $request->link_growth_research_photo;
        $file_growth_research_photo = $request->file_growth_research_photo;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $insert = DB::insert(" INSERT INTO yakopi_growth_research_photo 
        (id_growth_research_photo,id_detail_growth_research,link_growth_research_photo,file_growth_research_photo,created_by,created_time)
        VALUES (null,?,?,?,?,?,?)"
        ,[$id_detail_growth_research,$link_growth_research_photo,$file_growth_research_photo,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil ditambahkan"
        ];

    });

    Route::delete("/research/growthResearch/deletePhoto/{id}", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id;

        $cek = DB::select("SELECT * FROM yakopi_growth_research_photo WHERE id_growth_research_photo=?",[$id]);

        if(count($cek)>0){
            $cekDetail = DB::select("SELECT * FROM yakopi_detail_growth_research WHERE id_detail_growth_research=?",[$cek[0]->id_detail_growth_research]);

            $cekStatus = DB::select("SELECT * FROM yakopi_growth_research WHERE id_growth_research=?",[$cekDetail[0]->id_growth_research]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_growth_research_photo WHERE id_growth_research_photo=?",[$id]);

                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus karena sudah dilaporkan"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak ditemukan"
            ];
        }
    });

    // GROWTH RESEARCH END





});
