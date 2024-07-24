<?php 
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Controllers\File;
use App\Libraries\Pdf;
use Dompdf\Dompdf;

class Generate extends BaseController {

	public function __construct() {
	
		$this->parser = service('renderer');
		
	}

    public function generate_pdf()
    {
        
        $dompdf = new Dompdf();
        $data = [
            'imageSrc'    => $this->imageToBase64(ROOTPATH . '/public/image/profile.png'),
            'name'         => 'John Doe',
            'address'      => 'USA',
            'mobileNumber' => '000000000',
            'email'        => 'john.doe@email.com'
        ];
        $html = view('pdf_view', $data);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $filename = 'my_pdf_' . time() . '.pdf';
        $public_path = 'pdf/';
        
        // Ensure the directory exists
        if (!is_dir($public_path)) {
            mkdir($public_path, 0755, true);
        }
    
        $file_path = $public_path . $filename;
    
        // Write the file using native PHP file handling
        file_put_contents($file_path, $dompdf->output());
        return true;
        return redirect()->to(base_url('public/' . $filename));
    }
 
    private function imageToBase64($path) {
        $path = $path;
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return $base64;
    }
}