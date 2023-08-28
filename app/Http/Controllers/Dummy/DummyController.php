<?php

namespace App\Http\Controllers\Dummy;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\UserAuth\OtpController;
use App\Models\Post;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DummyController extends Controller
{
    private $media;
    public function __construct()
    {
        $this->media = new MediaController();
    }

    public function destroyPost($id)
    {
        try {
            $post = Post::find($id);
            if ($post) {
                // get media
                $medias = $post->medias()->get()->toArray();
                if ($post->delete()) {
                    if (count($medias) > 0) {
                        $this->media->destroyFile($medias[0]['path']);
                        $this->media->destroyFile($medias[0]['small_thumbnail']);
                        $this->media->destroyFile($medias[0]['medium_thumbnail']);
                        $this->media->destroyFile($medias[0]['large_thumbnail']);
                    }
                    return $this->statusCode(200, "Post deleted successfully");
                }
            }
            return $this->statusCode(404, "Post not found");
        } catch (\Throwable $e) {
            return $this->statusCode(500, $e->getMessage());
        }
    }

    public function UploadImage(Request $request)
    {
        $media = new MediaController();

        $path = 'dummytest/';
        $thumbnail = $this->media->convertToWebP($request, $path, 'media', 1366, 768, 'medium');
        dd($thumbnail);
        return $thumbnail;
    }

    /**
     * return String w258272033737b6415F
     */
    public function randomString(): string
    {
        // generate a pin based on 2 * 7 digits + a random character
        $pin = mt_rand(1000000, 9999999)
            . mt_rand(1000000, 9999999)
            . Str::random(5);
        // shuffle the result
        $string = str_shuffle($pin);
        return $string;
    }

    private function generateUniqueProfileInviteLink()
    {
        $link = $string = '';
        do {
            $string = $this->randomString();
            $chk = Profile::whereToken($string)->count();
        } while ($chk > 0);
        $baseUrl = request()->host();
        $link = $baseUrl . '/profile/' . $string;
        return ['link' => $link, 'token' => $string];
    }

    private function makeToken(): string
    {
        $uniqueToken = '';
        do {
            $uniqueToken = $this->randomString();
            $cnt = User::whereToken($uniqueToken)->count();
        } while ($cnt > 0);
        return $uniqueToken;
    }

    public function newUser()
    {
        try {
            // $otpController = new OtpController();
            // $me = [];
            // $me[] = User::whereEmail('abcdef.abcdef9336@gmail.com')->first();
            // $me[] = User::whereEmail('thinkactivemedia@gmail.com')->first();
            // $emails = [];
            $oldUsers = self::jsonData();

            DB::transaction(function () use ($oldUsers) {
                $usersData = [];
                $profileData = [];
                $professionData = [];

                foreach ($oldUsers as $k => $v) {
                    $linkArr = $this->generateUniqueProfileInviteLink();
                    $dateOfBirth = Carbon::parse($v['date_of_birth'])->format('Y-m-d');
                    $password = Hash::make($this->randomString());
                    $token = $this->makeToken();

                    $usersData[] = [
                        'email' => $v['email'],
                        'first_name' => $v['first_name'],
                        'last_name' => $v['last_name'],
                        'gender' => $v['gender'],
                        'password' => $password,
                        'date_of_birth' => $dateOfBirth,
                        'private' => $v['private'],
                        'email_verified_at' => null,
                        'token' => $token,
                        'status' => $v['status'],
                        'welcome_sent' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $profileData[] = [
                        'country' => $v['country'],
                        'invite_link' => $linkArr['link'],
                        'token' => $linkArr['token'],
                        'bio' => $v['bio'] ?? null,
                        'picture' => $v['picture'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $professionData[] = [
                        'profession' => $v['profession'] ?? null,
                        'linkedin' => $v['linkedin'] ?? null ?? null,
                        'facebook' => $v['facebook'] ?? null,
                        'youtube' => $v['youtube'] ?? null,
                        'twitter' => $v['twitter'] ?? null,
                        'instagram' => $v['instagram'] ?? null,
                        'website' => $v['website'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                User::insertOrIgnore($usersData);
                $users = User::whereIn('email', array_column($usersData, 'email'))->get();

                foreach ($users as $index => $user) {
                    $user->profile()->create($profileData[$index]);
                    $user->profession()->create($professionData[$index]);
                    (new CountryController())->storeCountry($user->id);
                }

                // return $emails;
                // when code get's here, it means it's successful
                // send mass verification mail
                // return $otpController->sendMassOTP($emails);
            });

            return $this->statusCode(200, "Accounts created successfully");
        } catch (\Throwable $e) {
            return $this->statusCode(422, $e->getMessage());
        }
    }

    private static function jsonData(): array
    {
        return [
            [
                "email" => "marianafruitdove@gmail.com",
                "status" => 1,
                "first_name" => "Mariana",
                "last_name" => "Carvalho",
                "profession" => "Biologist",
                "picture" => "profile_picture/default.png",
                "gender" => "female",
                "private" => 1,
                "country" => "United Kingdom",
                "date_of_birth" => "8/23/1977"
            ],
            [
                "email" => "akubrown.nb@gmail.com",
                "status" => 1,
                "first_name" => "Nancy",
                "last_name" => "B",
                "profession" => "Student",
                "picture" => "profile_picture/default.png",
                "gender" => "female",
                "private" => 0,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "5/1/1994"
            ],
            [
                "email" => "naak579@gmail.com",
                "status" => 1,
                "first_name" => "Naa",
                "last_name" => "Kordey",
                "profession" => "designer",
                "picture" => "profile_picture/default.png",
                "gender" => "female",
                "private" => 0,
                "country" => "gh",
                "date_of_birth" => "3/2/1998"
            ],
            [
                "email" => "lavajoe2011@gmail.com",
                "status" => 1,
                "first_name" => "Joseph Kwasi",
                "last_name" => "Afrifa",
                "profession" => "Researcher",
                "picture" => "profile_picture/default.png",
                "gender" => "male",
                "private" => 1,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "11/14/1991"
            ],
            [
                "email" => "alfredasihene@gmail.com",
                "status" => 0,
                "first_name" => "Alfred",
                "last_name" => "Asihene",
                "profession" => "Security consultant",
                "picture" => "profile_picture/default.png",
                "gender" => "male",
                "private" => 0,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "7/26/1967"
            ],
            [
                "email" => "sosual12@gmail.com",
                "status" => 0,
                "first_name" => "Alfred",
                "last_name" => "Sosu",
                "profession" => "Photographer",
                "picture" => "profile_picture/default.png",
                "gender" => "male",
                "private" => 0,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "1/5/1990"
            ],
            [
                "email" => "arkhurst@gmail.com",
                "status" => 1,
                "first_name" => "Papa",
                "last_name" => "Arkhurst",
                "profession" => "Facilitator, Trainer and Coach",
                "picture" => "profile_picture/WhatsApp_Image_2022-11-11_at_5.43.56_AM.jpeg",
                "gender" => "male",
                "bio" => "Papa Arkhurst is just COOL; he is a Connector, an optimist, an optimiser and a leadership enthusiast (COOL) who believes his purpose in life is to deal hope and to inspire the next generation to be the solutions Africa needs. He coined the word OPTIMISTIZING as an opportunity to showcase his passion for building optimism in the people he engages.
         He connects people through multiple platforms he has set up and does it with optimism, spreading hope wherever he goes. He optimises by providing TLC, that is, facilitating Team building workshops and strategy sessions, providing leadership training and providing communication and public speaking coaching.

         He has optimised individuals from students to C-level people to organisations and has been involved in various national projects. He is the charter and past president of the Rotary Club of Accra Speakmasters and past Division Director for Ghana Toastmasters. He serves as a mentor and Board member for a number of organisations.
         He is currently working on an ambitious venture called the MOTH Next-generation Initiative and is looking for passionate and ambitious impact-driven students willing to be a part of the change Ghana needs.

         His life quest is to transform the world by building
         Better Teams
         Better Leaders
         Better communicators",
                "docs" => "biography/Untitled_document.edited.docx",
                "private" => 0,
                "country" => "Ghana",
                "date_of_birth" => "6/22/1978"
            ],
            [
                "email" => "onumahja@gmail.com",
                "status" => 1,
                "first_name" => "Justina",
                "last_name" => "Onumah",
                "profession" => "Innovation and Development Economist",
                "picture" => "profile_picture/Dr_Justina_A_Onumah.jpg",
                "gender" => "female",
                "bio" => "Profile:  Justina Adwoa Onumah (PhD)
         Dr. Justina Adwoa Onumah is a Senior Research Scientist at the Science and Technology Policy Research Institute (STEPRI) of the Council for Scientific and Industrial Research (CSIR), Ghana. She holds a PhD in Development Studies, an MPhil in Agricultural Economics, both from the University of Ghana, and a BSc in Agricultural Technology (Economics and Extension option) from the University for Development Studies, Ghana. In addition to these, she has had postgraduate exchanges at the University of Guelph, Canada, University of Bonn, Germany, and Aalborg University, Denmark. Her research interests are in the fields of impact assessment, innovation systems, research-policy-industry linkages, rural development, food security, poverty/welfare analysis, technology transfer, and science policy. She has authored publications in these fields and gained over 10 years of rich research experience through involvement in multiple donor-funded projects and consulting for reputable organisations such as the United Nations, Solidaridad West Africa (SWA), UK Department for International Development (DFID), among others. Dr J. Onumah is an award-winning researcher and international speaker with several awards and fellowships to her credit. In 2021, one of her PhD papers was adjudged the Best PhD Student Paper presented at the GLOBELICS conference held in Costa Rica, becoming the first Ghanaian to receive such recognition from that Conference. She is also a 2022 Fellow of the prestigious Mandela Washington Fellowship for young African leaders by the United States Department of State where she received leadership training at the University of Delaware. Dr J. Onumah is also a 2023 Fellow of the Structural Transformation of African and Asian Agriculture and Rural Spaces (STAAARS+) Fellowship Programme hosted by the Cornell University in USA.
         Aside research, Dr J. Onumah is passionate about creating awareness of STEM and advocating for evidence use in policy making. She has gained rich experience in policy and stakeholder engagements on science over the years. She holds certification in Evidence and Policy from the European Commission Joint Research Centre and in Science Diplomacy from The World Academy of Sciences (TWAS). Dr J. Onumah is the Next Einstein Forum (NEF) Ambassador for Ghana, an initiative aimed at promoting science in Africa. She is also a mentor and serves on initiatives such as the Ghana Science and Technology Explorer Prize and Karpowership’s Girl Power program, all aimed at putting a spotlight on science and raising the next generation of scientists. She is a Member of the Board of Directors for the Center for Knowledge and Research Management (PACKS Africa), whose mission is to influence the use of research and other forms of knowledge in the development of policies in Africa. Her passion is to see more women and action put into STEM through advocacy, mentorship, and stronger research-industry-policy linkages. She fulfils this passion by organising mentorship sessions for young girls in the pursuit of careers in STEM and other non-STEM fields, hosting of Africa science weeks where she puts a spotlight on scientific advancement in Ghana, and holding research-policy engagements. She has impacted the lives of hundreds of girls through mentorship in Ghana and hopes to increase her impact by expanding her mentorship initiative to reach more girls, especially in underprivileged communities.

         Dr. Justina Adwoa Onumah is a Senior Research Scientist at the Science and Technology Policy Research Institute (STEPRI) of the Council for Scientific and Industrial Research (CSIR), Ghana. She holds a PhD in Development Studies, an MPhil in Agricultural Economics, both from the University of Ghana, and a BSc in Agricultural Technology (Economics and Extension option) from the University for Development Studies, Ghana. In addition to these, she has had postgraduate exchanges at the University of Guelph, Canada, University of Bonn, Germany, and Aalborg University, Denmark. Her research interests are in the fields of impact assessment, innovation systems, research-policy-industry linkages, rural development, food security, poverty/welfare analysis, technology transfer, and science policy. She has authored publications in these fields and gained over 10 years of rich research experience through involvement in multiple donor-funded projects and consulting for reputable organisations such as the United Nations, Solidaridad West Africa (SWA), UK Department for International Development (DFID), among others. Dr J. Onumah is an award-winning researcher and international speaker with several awards and fellowships to her credit. In 2021, one of her PhD papers was adjudged the Best PhD Student Paper presented at the GLOBELICS conference held in Costa Rica, becoming the first Ghanaian to receive such recognition from that Conference. She is also a 2022 Fellow of the prestigious Mandela Washington Fellowship for young African leaders by the United States Department of State where she received leadership training at the University of Delaware. Dr J. Onumah is also a 2023 Fellow of the Structural Transformation of African and Asian Agriculture and Rural Spaces (STAAARS+) Fellowship Programme hosted by the Cornell University in USA.
         Aside research, Dr J. Onumah is passionate about creating awareness of STEM and advocating for evidence use in policy making. She has gained rich experience in policy and stakeholder engagements on science over the years. She holds certification in Evidence and Policy from the European Commission Joint Research Centre and in Science Diplomacy from The World Academy of Sciences (TWAS). Dr J. Onumah is the Next Einstein Forum (NEF) Ambassador for Ghana, an initiative aimed at promoting science in Africa. She is also a mentor and serves on initiatives such as the Ghana Science and Technology Explorer Prize and Karpowership’s Girl Power program, all aimed at putting a spotlight on science and raising the next generation of scientists. She is a Member of the Board of Directors for the Center for Knowledge and Research Management (PACKS Africa), whose mission is to influence the use of research and other forms of knowledge in the development of policies in Africa. Her passion is to see more women and action put into STEM through advocacy, mentorship, and stronger research-industry-policy linkages. She fulfils this passion by organising mentorship sessions for young girls in the pursuit of careers in STEM and other non-STEM fields, hosting of Africa science weeks where she puts a spotlight on scientific advancement in Ghana, and holding research-policy engagements. She has impacted the lives of hundreds of girls through mentorship in Ghana and hopes to increase her impact by expanding her mentorship initiative to reach more girls, especially in underprivileged communities.",
                "docs" => "biography/Profile_Justina_Onumah_1222_7DpnneA.docx",
                "private" => 0,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "3/30/1987"
            ],
            [
                "email" => "paul.insua-cao@rspb.org.uk",
                "status" => 0,
                "first_name" => "Paul",
                "last_name" => "Insua",
                "profession" => "conservation",
                "picture" => "profile_picture/default.png",
                "gender" => "male",
                "private" => 1,
                "country" => "United Kingdom",
                "date_of_birth" => "12/19/2022"
            ],
            [
                "email" => "naaashorkormd@gmail.com",
                "status" => 1,
                "first_name" => "Naa Ashorkor",
                "last_name" => "Mensah-Doku",
                "profession" => "Journalist",
                "picture" => "profile_picture/5E990D73-7F77-49CB-93B2-FC2A74FF2805.jpeg",
                "gender" => "female",
                "bio" => "<p style=\"text-align:justify;\"><span style=\"font-size: 12px;\">I am</span><span style=\"color: rgb(0,0,0);background-color: transparent;font-size: 12pt;font-family: Times New Roman;\"> a Ghanaian broadcaster, actor, and entrepreneur. </span></p>
                <p style=\"text-align:justify;\"><span style=\"color: rgb(0,0,0);background-color: transparent;font-size: 12pt;font-family: Times New Roman;\">I hold an MA in Public Relations, an LLB (Law degree), and a BA in Communication Studies.</span><br></p>
                <p style=\"text-align:justify;\"><span style=\"color: rgb(0,0,0);background-color: transparent;font-size: 12pt;font-family: Times New Roman;\">I am best known for starring in the award-winning movie 'The Perfect Picture' (2009) and 'The Perfect Picture: Ten Years Later'.</span><br></p>
                <p style=\"text-align:justify;\"><span style=\"color: rgb(0,0,0);background-color: transparent;font-size: 12pt;font-family: Times New Roman;\">Presently, I am the host of 'We Got This Africa,' a self-produced TV show created to give African women a platform to share difficult life experiences. On radio, I'm the host of 'Between Hours' &amp; 'Just Us' on Asaase Radio.</span></p>
                <p style=\"text-align:justify;\"><span style=\"color: rgb(0,0,0);background-color: transparent;font-size: 12pt;font-family: Times New Roman;\">As part of my social responsibility and my love for education, I worked as the 'voice on' the GH4STEM campaign, an initiative created by WeGo Innovate to promote practical STEM education in Ghana.</span><br></p>
                <p style=\"text-align:justify;\"><span style=\"color: rgb(0,0,0);background-color: transparent;font-size: 12pt;font-family: Times New Roman;\">I am currently a brand ambassador for Frytol cooking oil and Wateraid Ghana.</span><br><br></p>",
                "docs" => "biography/Naa_Ashorkor_Profile.docx",
                "private" => 0,
                "country" => "Ghana",
                "date_of_birth" => "11/24/1988"
            ],
            [
                "email" => "desiree.bell@obamaalumni.com",
                "status" => 1,
                "first_name" => "Desiree",
                "last_name" => "Bell",
                "profession" => "CEO",
                "picture" => "profile_picture/default.png",
                "gender" => "female",
                "private" => 0,
                "country" => "United State",
                "date_of_birth" => "1/5/1978"
            ],
            [
                "email" => "esianyohbernard@gmail.com",
                "status" => 0,
                "first_name" => "Bernard",
                "last_name" => "esianyoh",
                "profession" => "Creative Designer",
                "picture" => "profile_picture/default.png",
                "gender" => "male",
                "private" => 0,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "4/16/1991"
            ],
            [
                "email" => "gwyn_gee@yahoo.com",
                "status" => 1,
                "first_name" => "Gwen",
                "last_name" => "Addo",
                "profession" => "CEO/BUSINESS STRATEGIST",
                "picture" => "profile_picture/2A29E072-B77F-48C3-A60C-D7E5E89A88D9.jpeg",
                "gender" => "female",
                "private" => 0,
                "country" => "Ghana",
                "date_of_birth" => "10/10/2022"
            ],
            [
                "email" => "fdeegbejr@gmail.com",
                "status" => 1,
                "first_name" => "Fred Mawuli",
                "last_name" => "Deegbe",
                "profession" => "consultant",
                "picture" => "profile_picture/default.png",
                "gender" => "male",
                "private" => 0,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "6/21/1984"
            ],
            [
                "email" => "smensahdoku@gmail.com",
                "status" => 1,
                "first_name" => "Stephanie",
                "last_name" => "Doku",
                "profession" => "Graphic Designer",
                "picture" => "profile_picture/043B6ED4-C371-468E-8CEB-75B6EDABDD86.jpeg",
                "gender" => "female",
                "private" => 0,
                "country" => "Ghana",
                "date_of_birth" => "12/3/1990"
            ],
            [
                "email" => "addonghana@gmail.com",
                "status" => 1,
                "first_name" => "Benjamin",
                "last_name" => "Adadevoh",
                "profession" => "Digital Marketing",
                "picture" => "profile_picture/IMG_8494.jpg",
                "gender" => "male",
                "private" => 0,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "12/20/2022"
            ],
            [
                "email" => "k.boafo247@gmail.com",
                "status" => 1,
                "first_name" => "Kwame",
                "last_name" => "Boafo",
                "profession" => "Conservationists",
                "picture" => "profile_picture/default.png",
                "gender" => "male",
                "private" => 0,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "8/30/1986"
            ],
            [
                "email" => "petra.asamoah@gmail.com",
                "status" => 1,
                "first_name" => "Petra Aba",
                "last_name" => "Asamoah",
                "profession" => "Marketing Executive",
                "picture" => "profile_picture/default.png",
                "gender" => "female",
                "private" => 0,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "3/24/1983"
            ],
            [
                "email" => "emmashior@yahoo.com",
                "status" => 1,
                "first_name" => "Emmanuel",
                "last_name" => "Agbemashior",
                "profession" => "Engineer",
                "picture" => "profile_picture/BA008B39-474C-4B80-8AAA-C21D87394786.jpeg",
                "gender" => "male",
                "private" => 0,
                "country" => "United State",
                "date_of_birth" => "12/21/1977"
            ],
            [
                "email" => "abi.sails.owusuaa@gmail.com",
                "status" => 1,
                "first_name" => "Abigail",
                "last_name" => "Owusuaa",
                "profession" => "Administrative Consultant",
                "picture" => "profile_picture/D7283EDF-1DDD-474A-9B22-32E9757CB185.jpeg",
                "gender" => "female",
                "bio" => "<p style=\"text-align:start;\"><span style=\"color: rgb(0,0,0);font-size: 17px;font-family: UICTFontTextStyleBody;\">I am an experienced person in the area of social media management and marketing, administration, communication, journalism, and customer service. I have worked for both National and Multi-National Companies and exposed to the dynamics of effective administrative work and customer service particularly.</span></p>
                <p style=\"text-align:start;\"><span style=\"color: rgb(0,0,0);font-size: 17px;font-family: UICTFontTextStyleBody;\">I am also a passionate, enthusiastic, fast learner, and results-oriented professional. I have over four (4) years of practical experience and over the years, I have gained hands-on experience with several social media tools (Falcon, Zendesk) and apps, performing user acceptance tests, managing social media platforms of individuals, NGO’s (Love’s Closet Foundation and Facebook page of Young Global Leaders Network-Ghana Chapter), government organization (Afribahamas), and telecommunication (MTN Ghana). While at it, I maintained and continue to ensure excellent relations that support, prepare, and promote the overall objectives of the company.</span></p>
                <p style=\"text-align:start;\"><span style=\"color: rgb(0,0,0);font-size: 17px;font-family: UICTFontTextStyleBody;\">I initiated and led the successful implementation of the use of emojis on MTN Ghana's digital platform to communicate to our audience, creating a comfortable aura for assistance as well as speeding our response time. I also possess excellent writing skills, communication skills, teamwork skills, multi-tasking skills, research, and analytical skills which have been honed due to my constant interaction with customers online and on the phone, as well as my media time experience in print and interaction with volunteers.</span></p>
                <p style=\"text-align:start;\"><span style=\"color: rgb(0,0,0);font-size: 17px;font-family: UICTFontTextStyleBody;\">I am knowledgeable in Office 365, Oracle, Google Drive, Excel formulas, PowerPoint, Teams, Excel, Publisher, and Canva for designing posts for the accounts I manage, Zoom, and Outlook among others.</span></p>
                <p style=\"text-align:start;\"><span style=\"color: rgb(0,0,0);font-size: 17px;font-family: UICTFontTextStyleBody;\">Currently, I work with the Love’s Closet Foundation, an NGO which focuses on healthcare and early childhood development as an administrative consultant. In my line of work, I work closely with members to manage 53 volunteers as well as volunteer with the Young Global Leaders Network-Ghana Chapter, where upon setting up their social media page, I managed and grew it into the 200’s in a month. (Name of page-Young Global Leaders Network-Ghana Chapter). I am a good swimmer, love traveling, and have some taekwondo skills too.</span></p>",
                "private" => 1,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "8/22/1996"
            ],
            [
                "email" => "doslynna@gmail.com",
                "status" => 1,
                "first_name" => "Doris",
                "last_name" => "mensah-wonkyi",
                "profession" => "Research Scientist",
                "picture" => "profile_picture/default.png",
                "gender" => "female",
                "private" => 0,
                "country" => "Ghana",
                "date_of_birth" => "11/18/2022"
            ],
            [
                "email" => "dinahmarri@gmail.com",
                "status" => 1,
                "first_name" => "dinah",
                "last_name" => "marri",
                "profession" => "Research scientist",
                "picture" => "profile_picture/default.png",
                "gender" => "female",
                "private" => 0,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "2/2/1975"
            ],
            [
                "email" => "agalrd@gmail.com",
                "status" => 1,
                "first_name" => "Raymond",
                "last_name" => "Agalga",
                "profession" => "Research Scientist",
                "picture" => "profile_picture/default.png",
                "gender" => "male",
                "private" => 0,
                "country" => "Ghana",
                "date_of_birth" => "5/1/1986"
            ],
            [
                "email" => "dorisdats.akua@gmail.com",
                "status" => 1,
                "first_name" => "Doris",
                "last_name" => "Dzimega",
                "profession" => "Research Scientist",
                "picture" => "profile_picture/default.png",
                "gender" => "female",
                "private" => 0,
                "country" => "Ghana",
                "date_of_birth" => "4/20/1977"
            ],
            [
                "email" => "dawuud2000@gmail.com",
                "status" => 1,
                "first_name" => "Abdallah M. A.",
                "last_name" => "Dawood",
                "profession" => "Research Scientist",
                "picture" => "profile_picture/default.png",
                "gender" => "male",
                "private" => 0,
                "country" => "Ghana",
                "date_of_birth" => "10/20/1979"
            ],
            [
                "email" => "valbempong911@gmail.com",
                "status" => 1,
                "first_name" => "Valerie Efua Kwansima",
                "last_name" => "Bempong",
                "profession" => "Lecturer",
                "picture" => "profile_picture/BA254B18-E789-44F6-A73E-757D702E91D6.jpeg",
                "gender" => "female",
                "private" => 0,
                "country" => "Ghana",
                "date_of_birth" => "8/16/1991"
            ],
            [
                "email" => "henry.odoi@gaec.gov.gh",
                "status" => 0,
                "first_name" => "Henry Cecil",
                "last_name" => "Odoi",
                "profession" => "Nuclear Engineering",
                "picture" => "profile_picture/default.png",
                "gender" => "male",
                "private" => 1,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "11/19/1973"
            ],
            [
                "email" => "hencilod@gmail.com",
                "status" => 0,
                "first_name" => "Henry Cecil",
                "last_name" => "Odoi",
                "profession" => "Nuclear Engineering",
                "picture" => "profile_picture/default.png",
                "gender" => "male",
                "private" => 1,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "11/19/1973"
            ],
            [
                "email" => "makeba.boateng@gmail.com",
                "status" => 0,
                "first_name" => "Makeba",
                "last_name" => "Boateng",
                "profession" => "founder",
                "picture" => "profile_picture/default.png",
                "gender" => "female",
                "private" => 0,
                "country" => "Ghana (Ghana)",
                "date_of_birth" => "11/30/1972"
            ],
            [
                "email" => "agbata@coliba.com.gh",
                "status" => 0,
                "first_name" => "Prince",
                "last_name" => "Agbata",
                "profession" => "Social Entreprenuer",
                "picture" => "profile_picture/default.png",
                "gender" => "male",
                "private" => 1,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "5/18/1991"
            ],
            [
                "email" => "giftyarkoh81@gmail.com",
                "status" => 0,
                "first_name" => "Gifty",
                "last_name" => "Arkoh",
                "profession" => "Certified VEX Robotics Instructor",
                "picture" => "profile_picture/default.png",
                "gender" => "female",
                "private" => 1,
                "country" => "Ghana (Gaana)",
                "date_of_birth" => "6/22/2003"
            ],
            [
                "email" => "abocco@gmail.com",
                "status" => 0,
                "first_name" => "Ato",
                "last_name" => "Ulzen-Appiah",
                "profession" => "Social Entrepreneur",
                "picture" => "profile_picture/default.png",
                "gender" => "male",
                "private" => 0,
                "country" => "Ghana",
                "date_of_birth" => "12/31/1983"
            ]
        ];
    }

    public static function oldEmails()
    {
        return [
            "marianafruitdove@gmail.com",
            "akubrown.nb@gmail.com",
            "naak579@gmail.com",
            "lavajoe2011@gmail.com",
            "alfredasihene@gmail.com",
            "sosual12@gmail.com",
            "arkhurst@gmail.com",
            "onumahja@gmail.com",
            "paul.insua-cao@rspb.org.uk",
            "naaashorkormd@gmail.com",
            "desiree.bell@obamaalumni.com",
            "esianyohbernard@gmail.com",
            "gwyn_gee@yahoo.com",
            "fdeegbejr@gmail.com",
            "smensahdoku@gmail.com",
            "addonghana@gmail.com",
            "k.boafo247@gmail.com",
            "petra.asamoah@gmail.com",
            "emmashior@yahoo.com",
            "abi.sails.owusuaa@gmail.com",
            "doslynna@gmail.com",
            "dinahmarri@gmail.com",
            "agalrd@gmail.com",
            "dorisdats.akua@gmail.com",
            "dawuud2000@gmail.com",
            "valbempong911@gmail.com",
            "henry.odoi@gaec.gov.gh",
            "hencilod@gmail.com",
            "makeba.boateng@gmail.com",
            "agbata@coliba.com.gh",
            "giftyarkoh81@gmail.com",
            "abocco@gmail.com"
        ];
    }

    public function removeAllOfThem()
    {
        $emails = self::oldEmails();
        User::whereIn('email', $emails)->delete();
        return $this->statusCode(200, "Users Deleted successfully");
    }
}
