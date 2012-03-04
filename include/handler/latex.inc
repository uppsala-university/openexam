<?php

//
// Copyright (C) 2011 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   include/handler/latex.inc
// Author: Anders Lövgren
// Date:   2011-01-29
//
// LaTeX string to image handler. This is an example on using locally installed
// programs for generating the desired output image.
//

class LatexHandler implements HandlerType
{

        const mime = "image/png";

        public function process($data, $path)
        {
                $dir = getcwd();
                chdir(dirname($path));

                $files->tex = sprintf("%s.tex", $path);
                $files->dvi = sprintf("%s.dvi", $path);
                $files->log = sprintf("%s.log", $path);
                $files->aux = sprintf("%s.aux", $path);

                //
                // Write the LaTeX input file.
                //
                if (($handle = fopen($files->tex, "w"))) {
                        fprintf($handle, "\documentclass[10pt]{article}\n");

                        fprintf($handle, "%% add additional packages here\n");
                        fprintf($handle, "\usepackage[utf8]{inputenc}\n");
                        fprintf($handle, "\usepackage{amsmath}\n");
                        fprintf($handle, "\usepackage{amsfonts}\n");
                        fprintf($handle, "\usepackage{amssymb}\n");
                        fprintf($handle, "\usepackage{pst-plot}\n");
                        fprintf($handle, "\usepackage{color}\n");

                        fprintf($handle, "\pagestyle{empty}\n");        // no page number
                        fprintf($handle, "\begin{document}\n");
                        fprintf($handle, "%s\n", $data);
                        fprintf($handle, "\end{document}\n");
                        fclose($handle);
                } else {
                        chdir($dir);
                        $message = sprintf("Failed create LaTeX input file %s", basename($files->tex));
                        throw new HandlerException($message);
                }

                //
                // Transform LaTeX input to DVI, then create the PNG-file.
                //
                exec(sprintf("/usr/bin/latex %s", $files->tex));
                exec(sprintf("/usr/bin/dvipng %s -T tight -o %s", $files->dvi, $path));

                //
                // Check that result file exists:
                //
                if (!file_exists($path)) {
                        chdir($dir);
                        $message = sprintf("Failed create image output file %s", basename($path));
                        throw new HandlerException($message);
                }

                //
                // Remove temporary files:
                //
                unlink($files->tex);
                unlink($files->dvi);
                unlink($files->log);
                unlink($files->aux);

                //
                // Switch back to original directory:
                //
                chdir($dir);
        }

        public function mime()
        {
                return self::mime;
        }

}

?>
