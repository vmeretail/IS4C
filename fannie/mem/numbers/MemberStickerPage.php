<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
if (!class_exists('FPDF')) {
    require(__DIR__ . '/../../src/fpdf/fpdf.php');
}
if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', __DIR__ . '/../../src/fpdf/font/');
}

class MemberStickerPage extends FanniePage {

    protected $title='Fannie - Print Member Stickers';
    protected $header='Print Member Stickers';

    public $description = '[Member Stickers] generates a PDF of member number stickers
    for use with membership paperwork.';
    public $themed = true;

    function preprocess()
    {
        $pairs = FormLib::get('pairs', false);
        if (FormLib::get_form_value('start',False) !== False){
            $pdf = new FPDF('P','in','Letter');
            $pdf->SetMargins(0.5,0.5,0.5);
            $pdf->SetAutoPageBreak(False,0.5);
            $pdf->AddPage();

            $start = FormLib::get_form_value('start');
            $x = 0.5;
            $y = 0.5;
            $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
            $pdf->SetFont('Gill','',16);
            for($i=0;$i<40;$i++){
                $pdf->SetXY($x,$y);
                $pdf->Cell(1.75,0.5,$start,0,0,'C');
                if (!$pairs) {
                    $start += 1;
                }
                $pdf->Cell(1.75,0.5,$start,0,0,'C');
                $start += 1;
                if ($i % 2 == 0) $x += (1.75*2)+0.5;
                else {
                    $x = 0.5;
                    $y += 0.5;
                }
            }
            $pdf->Close();
            $pdf->Output("mem stickers $start.pdf","I");

            return False;
        }
        return True;
    }

    function body_content()
    {
        return '<form action="MemberStickerPage.php" method="get">
        <p>
        Generate a sheet of member stickers<br />
        Format: Avery 5267<br />
        <div class="form-group">
            <label>Starting member number</label>
            <input type="number" name="start" class="form-control" required />
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="pairs" value="1" />
                Print pairs of numbers
            </label>
        </div>
        </p>
        <p>
        <button type="submit" class="btn btn-default">Get PDF</button>
        </p>
        </form>';
    }

    public function helpContent()
    {
        return '<p>Generate a PDF of member numbers. Layout is set for
            Avery 5267 label stock. Two labels are generated for each 
            member number - eighty labels to a page / forty unique member 
            numbers to a page. Two is often convient since one sticker
            can be attached to the member\'s paperwork and the other
            to the co-op\'s paperwork.</p>
            ';
    }
}

FannieDispatch::conditionalExec(false);

