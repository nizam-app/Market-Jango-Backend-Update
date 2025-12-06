
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Market Jango OTP</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    />
</head>
<body
    style="
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: #ffffff;
      font-size: 14px;
    "
>
<div
    style="
        max-width: 680px;
        margin: 0 auto;
        padding: 45px 30px 60px;
        background: #f9f9f9;
        background-image: url('https://marketjango-assets.s3.ap-northeast-2.amazonaws.com/email-background.jpg');
        background-repeat: no-repeat;
        background-size: cover;
        background-position: top center;
        font-size: 14px;
        color: #434343;
      "
>
    <header>
        <table style="width: 100%;">
            <tbody>
            <tr>
                <td>
                    <img
                        alt="Market Jango"
                        src="{{asset('asset/marketJango.png')}}"
                        height="100px"
                        style="border-radius:10%;"
                    />
                </td>
                <td style="text-align: right;">
                    <span
                        style="font-size: 16px; line-height: 30px; color: #100e0e;"
                    >{{ date('d M, Y') }}</span>
                </td>
            </tr>
            </tbody>
        </table>
    </header>

    <main>
        <div
            style="
            margin: 0;
            margin-top: 20px;
            padding: 50px 20px 50px;
            background: #ffffff;
            border-radius: 25px;
            text-align: center;
          "
        >
            <div style="width: 100%; max-width: 489px; margin: 0 auto;">
                <h1
                    style="
                margin: 0;
                font-size: 15px;
                font-weight: 600;
                color: #1f1f1f;
              "
                >
                    Your OTP Code
                </h1>
                <p
                    style="
                margin: 0;
                margin-top: 15px;
                font-size: 16px;
                font-weight: 500;
                ">
                    Hi {{$title}},
                </p>
                <p
                    style="
                margin: 0;
                margin-top: 15px;
                font-weight: 500;
                letter-spacing: 0.5px;
              "
                >
                    Thank you for using Market Jango. Use the following OTP to complete your verification process. Do not share this code with anyone.
                </p>
                <p

                    style="
                margin: 0;
                margin-top: 30px;
                font-size: 25px;
                font-weight: 700;
                letter-spacing: 10px;
                color: #d59292;
              "
                >
                    {{$otp}}
                </p>
            </div>
        </div>

        <p
            style="
            max-width: 400px;
            margin: 0 auto;
            margin-top: 70px;
            text-align: center;
            font-weight: 500;
            color: #8c8c8c;
          "
        >
            Need help? Contact us at
            <a
                href="mailto:support@marketjango.com"
                style="color: #1e88e5; text-decoration: none;"
            >support@marketjango.com</a>
            or visit our
            <a
                href="https://marketjango.com/help"
                target="_blank"
                style="color: #1e88e5; text-decoration: none;"
            >Help Center</a>
        </p>
    </main>

    <footer
        style="
          width: 100%;
          max-width: 490px;
          margin: 20px auto 0;
          text-align: center;
          border-top: 1px solid #e6ebf1;
        "
    >
        <p
            style="
            margin: 0;
            margin-top: 30px;
            font-size: 16px;
            font-weight: 600;
            color: #434343;
          "
        >
            Market Jango
        </p>
        <p style="margin: 0; margin-top: 6px; color: #434343;">
            123 Market Street, Dhaka, Bangladesh
        </p>
        <div style="margin: 0; margin-top: 14px;">
            <a href="#" target="_blank" style="display: inline-block;">
                <img width="36px" alt="Facebook" src="https://marketjango-assets.s3.ap-northeast-2.amazonaws.com/facebook-icon.png"/>
            </a>
            <a href="#" target="_blank" style="display: inline-block; margin-left: 8px;">
                <img width="36px" alt="Instagram" src="https://marketjango-assets.s3.ap-northeast-2.amazonaws.com/instagram-icon.png"/>
            </a>
            <a href="#" target="_blank" style="display: inline-block; margin-left: 8px;">
                <img width="36px" alt="Twitter" src="https://marketjango-assets.s3.ap-northeast-2.amazonaws.com/twitter-icon.png"/>
            </a>
            <a href="#" target="_blank" style="display: inline-block; margin-left: 8px;">
                <img width="36px" alt="YouTube" src="https://marketjango-assets.s3.ap-northeast-2.amazonaws.com/youtube-icon.png"/>
            </a>
        </div>
        <p style="margin: 0; margin-top: 16px; color: #434343;">
            © 2026 R2AIT. All rights reserved.
        </p>
    </footer>
</div>
</body>
</html>



