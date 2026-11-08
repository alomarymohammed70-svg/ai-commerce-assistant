import 'package:flutter_test/flutter_test.dart';
import 'package:ai_commerce_mobile/features/auth/data/models/user_model.dart';
import 'package:ai_commerce_mobile/features/auth/data/models/auth_response_model.dart';
import 'package:ai_commerce_mobile/features/products/data/models/canonical_product_model.dart';
import 'package:ai_commerce_mobile/features/products/data/models/product_listing_model.dart';
import 'package:ai_commerce_mobile/features/market_research/data/models/market_item_model.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/data/models/ai_analysis_model.dart';
import 'package:ai_commerce_mobile/features/ai_analysis/data/models/financial_metrics_model.dart';

void main() {
  group('Data Models Tests', () {
    test('UserModel.fromJson parses user fields correctly', () {
      final json = {
        'id': 1,
        'name': 'Test User',
        'email': 'test@example.com',
        'created_at': '2026-11-06T12:00:00Z',
      };

      final user = UserModel.fromJson(json);
      expect(user.id, 1);
      expect(user.name, 'Test User');
      expect(user.email, 'test@example.com');
    });

    test('AuthResponseModel.fromJson parses both wrapped and direct payloads', () {
      final wrappedJson = {
        'success': true,
        'message': 'Logged in successfully',
        'data': {
          'user': {'id': 1, 'name': 'Demo User', 'email': 'demo@example.com'},
          'token': '123|abcdef',
          'token_type': 'Bearer',
        },
      };

      final auth1 = AuthResponseModel.fromJson(wrappedJson);
      expect(auth1.user.id, 1);
      expect(auth1.user.name, 'Demo User');
      expect(auth1.token, '123|abcdef');
      expect(auth1.tokenType, 'Bearer');

      final directJson = {
        'user': {'id': 2, 'name': 'Direct User', 'email': 'direct@example.com'},
        'token': '456|ghijkl',
        'token_type': 'Bearer',
      };

      final auth2 = AuthResponseModel.fromJson(directJson);
      expect(auth2.user.id, 2);
      expect(auth2.token, '456|ghijkl');
    });

    test('CanonicalProductModel.fromJson parses product correctly', () {
      final json = {
        'id': 10,
        'title': 'Anker Wireless Charger',
        'sku': 'ANK-1234',
        'brand': 'Anker',
        'category': {'id': 2, 'name': 'Electronics'},
        'lowest_price': 15.99,
        'highest_price': 29.99,
        'base_currency': 'USD',
        'active_listings_count': 3,
        'latest_opportunity_score': 85.5,
      };

      final product = CanonicalProductModel.fromJson(json);
      expect(product.id, 10);
      expect(product.name, 'Anker Wireless Charger');
      expect(product.brand, 'Anker');
      expect(product.categoryName, 'Electronics');
      expect(product.lowestPrice, 15.99);
      expect(product.highestPrice, 29.99);
      expect(product.activeListingsCount, 3);
      expect(product.latestOpportunityScore, 85.5);
    });

    test('ProductListingModel.fromJson parses listing and seller signals', () {
      final json = {
        'id': 101,
        'external_id': 'ebay_123',
        'title': 'Anker Qi Fast Charger - eBay Offer',
        'source': {'name': 'eBay Marketplace', 'code': 'ebay'},
        'price': 18.50,
        'currency': 'USD',
        'raw_payload': {
          'seller': {
            'username': 'TechStoreUSA',
            'feedbackPercentage': 99.4,
            'feedbackScore': 4200,
          },
        },
        'is_available': true,
      };

      final listing = ProductListingModel.fromJson(json);
      expect(listing.id, 101);
      expect(listing.title, 'Anker Qi Fast Charger - eBay Offer');
      expect(listing.sourceName, 'eBay Marketplace');
      expect(listing.price, 18.50);
      expect(listing.currency, 'USD');
      expect(listing.supplierUsername, 'TechStoreUSA');
      expect(listing.sellerFeedbackPercentage, 99.4);
      expect(listing.sellerFeedbackScore, 4200);
      expect(listing.isAvailable, true);
    });

    test('MarketItemModel.fromJson parses market research item correctly', () {
      final json = {
        'id': 1,
        'external_id': 'v1|123456789|0',
        'title': 'Apple AirPods Pro 2 USB-C',
        'price': 189.99,
        'currency': 'USD',
        'rating': 4.8,
        'reviews_count': 350,
        'is_available': true,
        'url': 'https://www.ebay.com/itm/123456789',
        'source': {
          'name': 'eBay Marketplace',
          'code': 'ebay',
        },
      };

      final item = MarketItemModel.fromJson(json);
      expect(item.id, 1);
      expect(item.externalId, 'v1|123456789|0');
      expect(item.title, 'Apple AirPods Pro 2 USB-C');
      expect(item.price, 189.99);
      expect(item.currency, 'USD');
      expect(item.rating, 4.8);
      expect(item.reviewsCount, 350);
      expect(item.isAvailable, true);
      expect(item.sourceCode, 'ebay');
    });

    test('FinancialMetricsModel.fromJson parses deterministic data truth', () {
      final json = {
        'purchase_price': 120.00,
        'selling_price': 199.99,
        'shipping_cost': {'value': 15.00, 'is_verified': true},
        'estimated_fees': 20.00,
        'other_costs': 0.00,
        'total_cost': 155.00,
        'estimated_profit': {'value': 44.99, 'status': 'CALCULATED_CONFIRMED'},
        'profit_margin_percent': '22.50',
        'return_on_investment_percent': '29.03',
        'is_profitable': true,
      };

      final metrics = FinancialMetricsModel.fromJson(json);
      expect(metrics.purchasePrice, 120.00);
      expect(metrics.sellingPrice, 199.99);
      expect(metrics.shippingCost, 15.00);
      expect(metrics.totalCost, 155.00);
      expect(metrics.estimatedProfit, 44.99);
      expect(metrics.profitMarginPercent, '22.50');
      expect(metrics.isProfitable, true);
      expect(metrics.isShippingVerified, true);
      expect(metrics.profitStatus, 'CALCULATED_CONFIRMED');
    });

    test('AIAnalysisModel.fromJson parses complete AI recommendation', () {
      final json = {
        'id': 55,
        'canonical_product_id': 10,
        'provider': 'openai',
        'model': 'gpt-4o-mini',
        'prompt_version': 'v1',
        'decision': 'BUY',
        'confidence': 0.88,
        'opportunity_score': 82.0,
        'risk_score': 24.5,
        'analysis': {
          'recommendation': {
            'decision': 'BUY',
            'confidence': 0.88,
            'summary': 'Excellent margin opportunity with low competition.',
          },
          'opportunity': {
            'score': 82.0,
            'level': 'HIGH',
            'justification': 'Strong spread between sourcing and selling price.',
          },
          'profitability': {
            'profit': '44.99',
            'margin': '22.5%',
            'assessment': 'High net return.',
          },
          'competition': {
            'level': 'LOW',
            'score': 20.0,
            'summary': 'Few sellers offering same condition.',
          },
          'risk': {
            'level': 'LOW',
            'score': 24.5,
            'factors': ['Market price fluctuation'],
          },
          'reasons': ['High margin > 20%', 'Trusted seller on source market'],
          'warnings': ['Verify return policy'],
          'action_plan': ['Place initial trial order of 10 units', 'Monitor eBay selling speed'],
          'data_truth_summary': {
            'observed_data_available': true,
            'missing_critical_data': [],
          },
        },
        'deterministic_metrics': {
          'purchase_price': 120.00,
          'selling_price': 199.99,
          'shipping_cost': 15.00,
          'estimated_fees': 20.00,
          'other_costs': 0.00,
          'total_cost': 155.00,
          'estimated_profit': 44.99,
          'profit_margin_percent': '22.50',
          'return_on_investment_percent': '29.03',
          'is_profitable': true,
        },
        'duration_ms': 540,
        'tokens_used': 620,
      };

      final analysis = AIAnalysisModel.fromJson(json);
      expect(analysis.id, 55);
      expect(analysis.decision, 'BUY');
      expect(analysis.confidence, 0.88);
      expect(analysis.opportunityScore, 82.0);
      expect(analysis.riskScore, 24.5);
      expect(analysis.analysis.recommendation.decision, 'BUY');
      expect(analysis.analysis.reasons.length, 2);
      expect(analysis.analysis.actionPlan.length, 2);
      expect(analysis.deterministicMetrics.estimatedProfit, 44.99);
      expect(analysis.tokensUsed, 620);
    });
  });
}
