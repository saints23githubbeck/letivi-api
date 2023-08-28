<!DOCTYPE html>
<html lang="en">

<head>
    <title>Letivi</title>
    <style>
        .cast {
            text-align: left;
            padding-top: 20px;
            padding-bottom: 20px;
        }

        .cast-img {
            width: 250px;
        }

        .container {
            padding-right: 15px;
            padding-left: 15px;
            margin-right: auto;
            margin-left: auto;
        }

        @media (min-width: 768px) {
            .container {
                width: 500px;
            }
        }

        @media (min-width: 992px) {
            .container {
                width: 500px;
            }
        }

        @media (min-width: 1200px) {
            .container {
                width: 600px;
            }
        }

        .container-banner {
            position: relative;
            text-align: center;
            color: white;
        }

        .center-align {
            text-align: center;
        }

        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }
    </style>
</head>

<body>
    <div class="container" style="padding-top: 10px">
        <div class="center-align">
            <a href="javascript:void(0);">
                <img height="auto" class="cast-img"
                    src="https://letivi-git-staging-letivi.vercel.app/static/media/left_icon_logo.94c26052b22fe6158ffd.jpg">
            </a>
            <br>

            @yield('content')

            <div
                style="font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:15px;font-weight:400;line-height:24px;text-align:center;">
                We're glad you're here</div>
            <br>

            <div
                style="font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:15px;font-weight:400;line-height:24px;text-align:center;">
                The Letivi Team.</div>
            <br>

            @yield('challenges')
            <br>

            <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
                style="float:none;display:inline-table;">
                <tr>
                    <td style="padding:4px;vertical-align:middle;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation"
                            style="background:#ffffff;border-radius:3px;width:20px;">
                            <tr>
                                <td style="font-size:0;height:20px;vertical-align:middle;width:20px;">
                                    {{-- facebook --}}
                                    <a href="https://www.facebook.com/letivieverywhere/" target="_blank"
                                        rel="noopener noreferrer">
                                        <img height="20"
                                            src="http://cdn.mcauto-images-production.sendgrid.net/f4131106624eab16/2d4f75d5-ad20-405c-847f-966f3b6ec424/22x22.png"
                                            style="border-radius:3px;display:block;" width="20">
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!--[if mso | IE]></td><td><![endif]-->
            <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
                style="float:none;display:inline-table;">
                <tr>
                    <td style="padding:4px;vertical-align:middle;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation"
                            style="background:#ffffff;border-radius:3px;width:20px;">
                            <tr>
                                <td style="font-size:0;height:20px;vertical-align:middle;width:20px;">
                                    {{-- twitter --}}
                                    <a href="http://twitter.com/letiviapp" target="_blank" rel="noopener noreferrer">
                                        <img height="20"
                                            src="http://cdn.mcauto-images-production.sendgrid.net/f4131106624eab16/963250bc-de57-469e-9cb6-46e2e77bfac7/22x22.png"
                                            style="border-radius:3px;display:block;" width="20">
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!--[if mso | IE]></td><td><![endif]-->
            <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
                style="float:none;display:inline-table;">
                <tr>
                    <td style="padding:4px;vertical-align:middle;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation"
                            style="background:#ffffff;border-radius:3px;width:20px;">
                            <tr>
                                <td style="font-size:0;height:20px;vertical-align:middle;width:20px;">
                                    {{-- linkedIn --}}
                                    <a href="https://www.linkedin.com/company/letiviapp" target="_blank"
                                        rel="noopener noreferrer">
                                        <img height="20"
                                            src="http://cdn.mcauto-images-production.sendgrid.net/f4131106624eab16/38fc3308-74a6-4abc-9612-c4e7d61e1c99/22x22.png"
                                            style="border-radius:3px;display:block;" width="20">
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!--[if mso | IE]></td><td><![endif]-->
            <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
                style="float:none;display:inline-table;">
                <tr>
                    <td style="padding:4px;vertical-align:middle;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation"
                            style="background:#ffffff;border-radius:3px;width:20px;">
                            <tr>
                                <td style="font-size:0;height:20px;vertical-align:middle;width:20px;">
                                    {{-- instagram --}}
                                    <a href="https://www.instagram.com/letiviapp/" target="_blank"
                                        rel="noopener noreferrer">
                                        <img height="20"
                                            src="http://cdn.mcauto-images-production.sendgrid.net/f4131106624eab16/100f59e0-14aa-46d9-ad0a-ea29e1e7bbe9/22x22.png"
                                            style="border-radius:3px;display:block;" width="20">
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!--[if mso | IE]></td><td><![endif]-->
            <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
                style="float:none;display:inline-table;">
                <tr>
                    <td style="padding:4px;vertical-align:middle;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation"
                            style="background:#ffffff;border-radius:3px;width:20px;">
                            <tr>
                                <td style="font-size:0;height:20px;vertical-align:middle;width:20px;">
                                    {{-- Youtube --}}
                                    <a href="https://youtube.com/@Letivieverywhere" target="_blank"
                                        rel="noopener noreferrer">
                                        <img height="20"
                                            src="http://cdn.mcauto-images-production.sendgrid.net/f4131106624eab16/3cf97ef4-c81e-46d2-84fe-30df8d17a573/22x22.png"
                                            style="border-radius:3px;display:block;" width="20">
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!--[if mso | IE]></td><td><![endif]-->
            <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
                style="float:none;display:inline-table;">
                <tr>
                    <td style="padding:4px;vertical-align:middle;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation"
                            style="background:#fffff;border-radius:3px;width:20px;">
                            <tr>
                                <td style="font-size:0;height:20px;vertical-align:middle;width:20px;">
                                    {{-- tiktok --}}
                                    <a href="https://www.tiktok.com/@letiviapp" target="_blank"
                                        rel="noopener noreferrer">
                                        <img height="20"
                                            src="http://cdn.mcauto-images-production.sendgrid.net/f4131106624eab16/c98101c7-b18f-4315-a45f-3b35a27b20cf/22x22.png"
                                            style="border-radius:3px;display:block;" width="20">
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
