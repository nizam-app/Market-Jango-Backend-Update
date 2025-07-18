import 'package:carousel_slider/carousel_options.dart';
import 'package:carousel_slider/carousel_slider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
class BuyerHomeScreen extends StatefulWidget {
  const BuyerHomeScreen({super.key});
  static const String routeName = '/buyerHomeScreen';

  @override
  State<BuyerHomeScreen> createState() => _BuyerHomeScreenState();
}

class _BuyerHomeScreenState extends State<BuyerHomeScreen> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding:  EdgeInsets.symmetric(horizontal: 20.w,),
          child: Column(
            children: [
              SizedBox(height: 20.h,),
              BuyerHomeSearchBar(),
              PromoSlider(),

            ],
          ),
        ),
      ),
    );
  }
}

class BuyerHomeSearchBar extends StatelessWidget {
  const BuyerHomeSearchBar({
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          flex: 2,
          child: TextFormField(
            decoration: InputDecoration(
              hintText: 'Search for products',
              prefixIcon: Icon(Icons.search,),
              isDense: true,
              filled: true,
              fillColor: Colors.white,
              contentPadding: EdgeInsets.symmetric(vertical: 12.h,),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(50.r),
                borderSide: BorderSide.none,
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(50.r),
                borderSide: BorderSide.none,
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(50.r),
                borderSide: BorderSide(color: Colors.grey), // ফোকাসে যেটা দেখাতে চান
              ),
            ), // থিম ইনহেরিট না করার জন্য
          ),
        ),
        SizedBox(width: 8.w),
        // Menu Icon
        InkWell(
          onTap: () {
           goToNotificationScreen(context);
          },
          child: Container(
            height: 35.h,
            width: 35.w,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                  color: Colors.black12,
                  blurRadius: 4.sp,
                  offset: Offset(0, 2),
                ),
              ],
            ),
            child: IconButton(
              icon: Icon(Icons.menu, size: 20.sp),
              onPressed: () {},
            ),
          ),
        ),
        SizedBox(width: 8.w),
    
        // Notification Icon
        InkWell(
          onTap: () {
            openingFilter(context);
          },
          child: Container(
            height: 35.h,
            width: 35.w,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                  color: Colors.black12,
                  blurRadius: 4,
                  offset: Offset(0, 2),
                ),
              ],
            ),
            child: IconButton(
              icon: Icon(Icons.notifications, size: 20.sp),
              onPressed: () {},
            ),
          ),
        ),
    
      ],
    );
  }
  void goToNotificationScreen(BuildContext context) {}
  void openingFilter(BuildContext context) {}
}

class PromoSlider extends StatefulWidget {
  @override
  _PromoSliderState createState() => _PromoSliderState();
}

class _PromoSliderState extends State<PromoSlider> {
  int _currentIndex = 0;
  final CarouselSliderController _controller = CarouselSliderController();

  final List<String> imageList = [
    'assets/images/promo1.jpg',
    'assets/images/promo2.jpg',
    'assets/images/promo3.jpeg',
  ];

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        SizedBox(height: 30.h,),
        CarouselSlider.builder(
          carouselController: _controller,
          itemCount: imageList.length,
          itemBuilder: (context, index, realIndex) {
            return ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: Image.asset(
                imageList[index],
                fit: BoxFit.cover,
                width: double.infinity,
              ),
            );
          },
          options: CarouselOptions(
            height: 158.h,
            autoPlay: true,
            autoPlayInterval: Duration(seconds: 3),
            enlargeCenterPage: false,
            viewportFraction: 1.0,
            onPageChanged: (index, reason) {
              setState(() {
                _currentIndex = index;
              });
            },
            scrollDirection: Axis.horizontal,
            reverse: false, // non-return back
            enableInfiniteScroll: true, // nonstop
          ),
        ),

        SizedBox(height: 12),

        // Dot Indicator
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: imageList.asMap().entries.map((entry) {
            return Container(
              width: _currentIndex == entry.key ? 24.0 : 8.0,
              height: 8.0,
              margin: EdgeInsets.symmetric(horizontal: 4.0),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(8),
                color: _currentIndex == entry.key
                    ? Colors.orange
                    : Colors.orange.withOpacity(0.3),
              ),
            );
          }).toList(),
        ),
      ],
    );
  }
}
